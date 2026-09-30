<?php
// app/Controllers/GovernanceController.php

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Audit.php';
require_once __DIR__ . '/../Security.php';

class GovernanceController {
    public function index() {
        Auth::requireLogin();
        $pdo = Database::getConnection();

        $year = isset($_GET['year']) && $_GET['year'] !== '' ? (int)$_GET['year'] : 2024;

        $stmt = $pdo->prepare("SELECT * FROM esg_governance_metrics WHERE period_year = ?");
        $stmt->execute([$year]);
        $current = $stmt->fetch();

        // All years history
        $history = $pdo->query("SELECT * FROM esg_governance_metrics ORDER BY period_year DESC")->fetchAll();

        // TCFD Risks list
        $tcfdRisks = [
            [
                'category' => '轉型風險 (Transition Risk)',
                'name' => '台灣環境部碳費徵收與歐盟 CBAM 關稅衝擊',
                'description' => '每公噸碳費預計 300~500 元起徵，直接推升範疇一二營運成本',
                'impact' => '高 (High)',
                'likelihood' => '極高 (Almost Certain)',
                'response' => '推動再生能源建置與低碳製程節能專案，設定每年 5% 減碳目標'
            ],
            [
                'category' => '實體風險 (Physical Risk)',
                'name' => '極端氣候強降雨與廠區暴雨淹水風險',
                'description' => '颱風降雨量加劇，可能造成廠區停電、停工或設備損毀',
                'impact' => '中 (Medium)',
                'likelihood' => '中 (Likely)',
                'response' => '廠區防洪閘門加高、配置雙備援發電機與定期防汛演練'
            ],
            [
                'category' => '轉型風險 (Transition Risk)',
                'name' => '客戶強制要求供應鏈綠電與 RE100 達標',
                'description' => '國際大廠要求 2030 年達成 50% 綠電使用率，否則可能喪失訂單',
                'impact' => '高 (High)',
                'likelihood' => '高 (Likely)',
                'response' => '簽署企業購售電合約 (CPPA) 與屋頂型太陽能案場併網'
            ]
        ];

        require __DIR__ . '/../../views/governance/index.php';
    }

    public function store() {
        Auth::requirePermission('manage_metrics');
        Security::requirePost('governance');

        $pdo = Database::getConnection();
        try {
            $year = Security::intInRange($_POST['period_year'] ?? null, 2000, 2100, '年度');
            $seats = Security::intInRange($_POST['board_seats_total'] ?? 0, 0, 255, '董事席次');
            $indep = Security::intInRange($_POST['independent_directors'] ?? 0, 0, $seats, '獨立董事席次');
            $female = Security::intInRange($_POST['female_directors'] ?? 0, 0, $seats, '女性董事席次');
            $meetings = Security::intInRange($_POST['board_meetings_count'] ?? 0, 0, 255, '會議次數');
            $attRate = Security::nonNegativeNumber($_POST['board_attendance_rate'] ?? 0, '出席率');
            if ($attRate > 100) throw new InvalidArgumentException('出席率不得超過 100%');
            $ethics = Security::intInRange($_POST['ethics_train_headcount'] ?? 0, 0, PHP_INT_MAX, '受訓人次');
            $whistleCases = Security::intInRange($_POST['whistleblower_cases'] ?? 0, 0, PHP_INT_MAX, '舉報件數');
            $whistleClosed = Security::intInRange($_POST['whistleblower_closed'] ?? 0, 0, $whistleCases, '舉報結案件數');
            $cyber = Security::intInRange($_POST['cyber_incidents_count'] ?? 0, 0, PHP_INT_MAX, '資安事件數');
            $penalties = Security::intInRange($_POST['legal_penalty_count'] ?? 0, 0, PHP_INT_MAX, '裁罰件數');
            $penaltyAmt = Security::nonNegativeNumber($_POST['legal_penalty_amount'] ?? 0, '裁罰金額');
        } catch (Throwable $e) {
            set_flash('danger', $e->getMessage());
            redirect('governance');
        }
        $lockStmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(workflow_status <> 'locked') AS unlocked FROM esg_ghg_emissions WHERE period_year = ?");
        $lockStmt->execute([$year]);
        $lock = $lockStmt->fetch();
        if ((int)$lock['total'] > 0 && (int)$lock['unlocked'] === 0) {
            set_flash('danger', '該年度已封存，治理指標不可再修改');
            redirect('governance', ['year' => $year]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO esg_governance_metrics
            (period_year, board_seats_total, independent_directors, female_directors, board_meetings_count, 
             board_attendance_rate, ethics_train_headcount, whistleblower_cases, whistleblower_closed, 
             cyber_incidents_count, legal_penalty_count, legal_penalty_amount, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            board_seats_total = VALUES(board_seats_total),
            independent_directors = VALUES(independent_directors),
            female_directors = VALUES(female_directors),
            board_meetings_count = VALUES(board_meetings_count),
            board_attendance_rate = VALUES(board_attendance_rate),
            ethics_train_headcount = VALUES(ethics_train_headcount),
            whistleblower_cases = VALUES(whistleblower_cases),
            whistleblower_closed = VALUES(whistleblower_closed),
            cyber_incidents_count = VALUES(cyber_incidents_count),
            legal_penalty_count = VALUES(legal_penalty_count),
            legal_penalty_amount = VALUES(legal_penalty_amount)
        ");
        $stmt->execute([
            $year, $seats, $indep, $female, $meetings, $attRate,
            $ethics, $whistleCases, $whistleClosed, $cyber, $penalties, $penaltyAmt
        ]);

        $idStmt = $pdo->prepare("SELECT id FROM esg_governance_metrics WHERE period_year = ?");
        $idStmt->execute([$year]);
        Audit::log('SAVE', 'esg_governance_metrics', (int)$idStmt->fetchColumn(), null, [
            'year' => $year,
            'board_seats' => $seats,
            'independent_directors' => $indep
        ]);

        set_flash('success', "公司治理指標 ({$year} 年度) 已成功保存！");
        redirect('governance', ['year' => $year]);
    }
}
