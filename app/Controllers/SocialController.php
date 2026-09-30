<?php
// app/Controllers/SocialController.php

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Audit.php';
require_once __DIR__ . '/../Security.php';

class SocialController {
    public function index() {
        Auth::requireLogin();
        $pdo = Database::getConnection();

        $year = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : 2024;
        $orgUnitId = !empty($_GET['org_unit_id']) ? (int)$_GET['org_unit_id'] : '';

        $sql = "
            SELECT s.*, o.unit_name, o.unit_code
            FROM esg_social_metrics s
            JOIN sys_org_units o ON s.org_unit_id = o.id
            WHERE 1=1
        ";
        $params = [];

        if (!Auth::hasRole(['admin', 'esg_lead', 'auditor'])) {
            $sql .= " AND s.org_unit_id = ?";
            $params[] = (int)Auth::user()['org_unit_id'];
        }

        if ($year) {
            $sql .= " AND s.period_year = ?";
            $params[] = $year;
        }
        if ($orgUnitId) {
            $sql .= " AND s.org_unit_id = ?";
            $params[] = $orgUnitId;
        }

        $sql .= " ORDER BY s.period_year DESC, s.org_unit_id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        // Calculate aggregated KPI totals for this year
        $summary = [
            'total_emp' => 0,
            'male_emp' => 0,
            'female_emp' => 0,
            'female_emp_ratio' => 0.0,
            'female_mgr' => 0,
            'disabled_emp' => 0,
            'work_hours' => 0.0,
            'injuries' => 0,
            'lost_days' => 0,
            'overall_fr' => 0.0,
            'overall_sr' => 0.0,
            'training_hours' => 0.0,
            'volunteer_hours' => 0.0,
            'donation' => 0.0
        ];

        foreach ($records as $r) {
            $summary['male_emp'] += (int)$r['male_employees'];
            $summary['female_emp'] += (int)$r['female_employees'];
            $summary['female_mgr'] += (int)$r['female_managers'];
            $summary['disabled_emp'] += (int)$r['disabled_employees'];
            $summary['work_hours'] += (float)$r['total_work_hours'];
            $summary['injuries'] += (int)$r['disabling_injury_count'];
            $summary['lost_days'] += (int)$r['lost_days_count'];
            $summary['training_hours'] += (float)$r['training_hours_total'];
            $summary['volunteer_hours'] += (float)$r['volunteer_hours'];
            $summary['donation'] += (float)$r['donation_amount_ntd'];
        }

        $summary['total_emp'] = $summary['male_emp'] + $summary['female_emp'];
        if ($summary['total_emp'] > 0) {
            $summary['female_emp_ratio'] = round(($summary['female_emp'] / $summary['total_emp']) * 100, 1);
        }
        if ($summary['work_hours'] > 0) {
            $summary['overall_fr'] = round(($summary['injuries'] * 1000000) / $summary['work_hours'], 4);
            $summary['overall_sr'] = round(($summary['lost_days'] * 1000000) / $summary['work_hours'], 4);
        }

        $orgs = $pdo->query("SELECT id, unit_name, unit_code FROM sys_org_units WHERE is_active = 1 ORDER BY id")->fetchAll();

        require __DIR__ . '/../../views/social/index.php';
    }

    public function store() {
        Auth::requirePermission('manage_metrics');
        Security::requirePost('social');

        $pdo = Database::getConnection();
        try {
            $orgUnitId = Security::intInRange($_POST['org_unit_id'] ?? null, 1, PHP_INT_MAX, '組織');
            $year = Security::intInRange($_POST['period_year'] ?? null, 2000, 2100, '年度');
            $male = Security::intInRange($_POST['male_employees'] ?? 0, 0, PHP_INT_MAX, '男性員工數');
            $female = Security::intInRange($_POST['female_employees'] ?? 0, 0, PHP_INT_MAX, '女性員工數');
            $disabled = Security::intInRange($_POST['disabled_employees'] ?? 0, 0, PHP_INT_MAX, '身障員工數');
            $femaleMgr = Security::intInRange($_POST['female_managers'] ?? 0, 0, PHP_INT_MAX, '女性主管數');
            $workHours = Security::nonNegativeNumber($_POST['total_work_hours'] ?? 0, '總工時');
            $injuries = Security::intInRange($_POST['disabling_injury_count'] ?? 0, 0, PHP_INT_MAX, '失能傷害次數');
            $lostDays = Security::intInRange($_POST['lost_days_count'] ?? 0, 0, PHP_INT_MAX, '損失工作日');
            $training = Security::nonNegativeNumber($_POST['training_hours_total'] ?? 0, '培訓時數');
            $volunteer = Security::nonNegativeNumber($_POST['volunteer_hours'] ?? 0, '志工時數');
            $donation = Security::nonNegativeNumber($_POST['donation_amount_ntd'] ?? 0, '捐贈金額');
        } catch (Throwable $e) {
            set_flash('danger', $e->getMessage());
            redirect('social');
        }
        $lockStmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(workflow_status <> 'locked') AS unlocked FROM esg_ghg_emissions WHERE period_year = ?");
        $lockStmt->execute([$year]);
        $lock = $lockStmt->fetch();
        if ((int)$lock['total'] > 0 && (int)$lock['unlocked'] === 0) {
            set_flash('danger', '該年度已封存，社會指標不可再修改');
            redirect('social', ['year' => $year]);
        }

        // Calculate FR and SR
        $fr = ($workHours > 0) ? round(($injuries * 1000000) / $workHours, 4) : 0.0;
        $sr = ($workHours > 0) ? round(($lostDays * 1000000) / $workHours, 4) : 0.0;

        $stmt = $pdo->prepare("
            INSERT INTO esg_social_metrics
            (org_unit_id, period_year, male_employees, female_employees, disabled_employees, female_managers, 
             total_work_hours, disabling_injury_count, lost_days_count, fr_value, sr_value, 
             training_hours_total, volunteer_hours, donation_amount_ntd, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            male_employees = VALUES(male_employees),
            female_employees = VALUES(female_employees),
            disabled_employees = VALUES(disabled_employees),
            female_managers = VALUES(female_managers),
            total_work_hours = VALUES(total_work_hours),
            disabling_injury_count = VALUES(disabling_injury_count),
            lost_days_count = VALUES(lost_days_count),
            fr_value = VALUES(fr_value),
            sr_value = VALUES(sr_value),
            training_hours_total = VALUES(training_hours_total),
            volunteer_hours = VALUES(volunteer_hours),
            donation_amount_ntd = VALUES(donation_amount_ntd)
        ");
        $stmt->execute([
            $orgUnitId, $year, $male, $female, $disabled, $femaleMgr,
            $workHours, $injuries, $lostDays, $fr, $sr,
            $training, $volunteer, $donation
        ]);

        $idStmt = $pdo->prepare("SELECT id FROM esg_social_metrics WHERE org_unit_id = ? AND period_year = ?");
        $idStmt->execute([$orgUnitId, $year]);
        Audit::log('SAVE', 'esg_social_metrics', (int)$idStmt->fetchColumn(), null, [
            'year' => $year,
            'total_emp' => $male + $female,
            'fr' => $fr,
            'sr' => $sr
        ]);

        set_flash('success', "社會面向指標 (FR: {$fr}, SR: {$sr}) 已成功保存！");
        redirect('social', ['year' => $year]);
    }
}
