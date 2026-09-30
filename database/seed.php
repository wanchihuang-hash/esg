<?php
/**
 * ESG System Database Seeder
 * Populates initial test users, org units, emission data, energy/water data, social & governance data.
 */

$host = '127.0.0.1';
$dbname = 'esg_system';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "Connected to database successfully.\n";

    // 1. Insert Org Units if empty
    $count = $pdo->query("SELECT COUNT(*) FROM sys_org_units")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO sys_org_units (id, parent_id, unit_code, unit_name, unit_type, country, address, is_active) VALUES
            (1, NULL, 'HQ', '綠能永續集團總部', 'group', 'Taiwan', '台北市信義區信義路五段7號', 1),
            (2, 1, 'COMP-TW', '台灣綠能科技股份有限公司', 'company', 'Taiwan', '台北市內湖區科技路100號', 1),
            (3, 2, 'PLANT-TY1', '桃園大園一廠 (智慧製造)', 'plant', 'Taiwan', '桃園市大園區工四路88號', 1),
            (4, 2, 'PLANT-TN2', '台南科學二廠 (綠能先進)', 'plant', 'Taiwan', '台南市新市區南科三路50號', 1),
            (5, 2, 'DEPT-ESH', '集團環安衛與永續發展處', 'dept', 'Taiwan', '台北市內湖區科技路100號6樓', 1);
        ");
        echo "Org units seeded.\n";
    }

    // 2. Insert Users if empty
    $count = $pdo->query("SELECT COUNT(*) FROM sys_users")->fetchColumn();
    if ($count == 0) {
        $users = [
            ['HQ', 'admin', 'admin', 'admin123', '系統管理員 (Super Admin)', 'admin@esg-corp.local', '02-8888-0001'],
            ['HQ', 'esg_lead', 'lead', 'lead123', '林永續 協理 (ESG Lead)', 'esg.lead@esg-corp.local', '02-8888-0002'],
            ['PLANT-TY1', 'reviewer', 'reviewer', 'reviewer123', '張廠長 (Plant Reviewer)', 'ty1.manager@esg-corp.local', '03-3333-1001'],
            ['PLANT-TY1', 'collector', 'collector', 'collector123', '李專員 (Data Collector)', 'collector@esg-corp.local', '03-3333-1002'],
            ['HQ', 'auditor', 'auditor', 'auditor123', '王查證員 (Lead Auditor)', 'auditor@kpmg-bureau.org', '02-2777-9999']
        ];

        $stmtOrg = $pdo->prepare("SELECT id FROM sys_org_units WHERE unit_code = ?");
        $stmtRole = $pdo->prepare("SELECT id FROM sys_roles WHERE role_code = ?");
        $stmtInsert = $pdo->prepare("
            INSERT INTO sys_users (org_unit_id, role_id, username, password_hash, full_name, email, phone, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");

        foreach ($users as $u) {
            $stmtOrg->execute([$u[0]]);
            $orgId = $stmtOrg->fetchColumn() ?: 1;
            $stmtRole->execute([$u[1]]);
            $roleId = $stmtRole->fetchColumn() ?: 1;
            $hash = password_hash($u[3], PASSWORD_BCRYPT);
            $stmtInsert->execute([$orgId, $roleId, $u[2], $hash, $u[4], $u[5], $u[6]]);
        }
        echo "Users seeded.\n";
    }

    // 3. Insert Additional Factors if needed
    $factors = [
        ['EF-R410A-FUG', 'R-410A 冷媒逸散排放係數 (IPCC AR6)', 'Scope1', '逸散排放', 'R-410A冷媒', 2088.000000, '公斤', 'IPCC 第六次評估報告 (AR6)', 2024, 1],
        ['EF-BUSINESS-FLIGHT', '國內外商務差旅航空碳排係數', 'Scope3', '差旅交通', '航空燃油', 0.150000, '人公里', '英國 DEFRA 2023', 2024, 1]
    ];
    $checkFactor = $pdo->prepare("SELECT id FROM esg_emission_factors WHERE factor_code = ?");
    $insertFactor = $pdo->prepare("
        INSERT INTO esg_emission_factors (factor_code, factor_name, scope_type, category, fuel_type, co2e_factor, unit, data_source, effective_year, is_current)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($factors as $f) {
        $checkFactor->execute([$f[0]]);
        if (!$checkFactor->fetchColumn()) {
            $insertFactor->execute($f);
        }
    }

    // 4. Insert Sample GHG Emissions for 2023 and 2024
    $ghgCount = $pdo->query("SELECT COUNT(*) FROM esg_ghg_emissions")->fetchColumn();
    if ($ghgCount == 0) {
        $adminId = $pdo->query("SELECT id FROM sys_users WHERE username = 'collector'")->fetchColumn() ?: 1;
        $reviewerId = $pdo->query("SELECT id FROM sys_users WHERE username = 'reviewer'")->fetchColumn() ?: 1;

        // Fetch factors
        $fElec = $pdo->query("SELECT id, co2e_factor FROM esg_emission_factors WHERE factor_code = 'EF-ELEC-2023'")->fetch();
        $fDiesel = $pdo->query("SELECT id, co2e_factor FROM esg_emission_factors WHERE factor_code = 'EF-DIESEL-FIX'")->fetch();
        $fGas = $pdo->query("SELECT id, co2e_factor FROM esg_emission_factors WHERE factor_code = 'EF-GASOLINE-MOB'")->fetch();
        $fNatGas = $pdo->query("SELECT id, co2e_factor FROM esg_emission_factors WHERE factor_code = 'EF-GAS-NATURAL'")->fetch();
        $fWater = $pdo->query("SELECT id, co2e_factor FROM esg_emission_factors WHERE factor_code = 'EF-WATER-TAP'")->fetch();
        $fWaste = $pdo->query("SELECT id, co2e_factor FROM esg_emission_factors WHERE factor_code = 'EF-WASTE-INCIN'")->fetch();

        $insertGhg = $pdo->prepare("
            INSERT INTO esg_ghg_emissions 
            (org_unit_id, period_year, period_month, scope_type, emission_source, factor_id, activity_amount, activity_unit, calculated_tco2e, invoice_no, proof_file_path, workflow_status, reviewer_id, reviewer_comment, reviewed_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $plants = [3 => '桃園大園一廠', 4 => '台南科學二廠'];

        // 2023 data (Locked/Approved historical baseline)
        foreach ($plants as $pId => $pName) {
            for ($m = 1; $m <= 12; $m++) {
                // Electricity (Scope 2)
                $kwh = ($pId == 3) ? (45000 + rand(-2000, 3000)) : (62000 + rand(-3000, 4000));
                $tco2 = round(($kwh * $fElec['co2e_factor']) / 1000, 4);
                $insertGhg->execute([$pId, 2023, $m, 'Scope2', "{$pName} 主變電站外購電力", $fElec['id'], $kwh, '度', $tco2, "TPC-2023-{$m}", null, 'locked', $reviewerId, '年度碳盤查經第三方查證完成鎖檔', '2024-03-15 10:00:00', $adminId]);

                // Diesel generator (Scope 1)
                $dieselL = rand(300, 600);
                $tco2Diesel = round(($dieselL * $fDiesel['co2e_factor']) / 1000, 4);
                $insertGhg->execute([$pId, 2023, $m, 'Scope1', "{$pName} 緊急備用柴油發電機", $fDiesel['id'], $dieselL, '公升', $tco2Diesel, "OIL-2023-{$m}", null, 'locked', $reviewerId, '查證通過', '2024-03-15 10:00:00', $adminId]);

                // Natural Gas (Scope 1)
                $gasM3 = rand(800, 1500);
                $tco2Gas = round(($gasM3 * $fNatGas['co2e_factor']) / 1000, 4);
                $insertGhg->execute([$pId, 2023, $m, 'Scope1', "{$pName} 鍋爐天然氣", $fNatGas['id'], $gasM3, '立方公尺', $tco2Gas, "GAS-2023-{$m}", null, 'locked', $reviewerId, '查證通過', '2024-03-15 10:00:00', $adminId]);

                // Tap water (Scope 3)
                $waterM3 = rand(1200, 2200);
                $tco2Water = round(($waterM3 * $fWater['co2e_factor']) / 1000, 4);
                $insertGhg->execute([$pId, 2023, $m, 'Scope3', "{$pName} 自來水碳足跡", $fWater['id'], $waterM3, '度', $tco2Water, "WTR-2023-{$m}", null, 'locked', $reviewerId, '查證通過', '2024-03-15 10:00:00', $adminId]);
            }
        }

        // 2024 data (Months 1 ~ 8 approved, Month 9 pending / draft)
        foreach ($plants as $pId => $pName) {
            for ($m = 1; $m <= 9; $m++) {
                $status = ($m <= 7) ? 'approved' : (($m == 8) ? 'pending' : 'draft');
                $revComment = ($status == 'approved') ? '核算數據與電費單相符，審核通過' : null;
                $revAt = ($status == 'approved') ? date('Y-m-d H:i:s', strtotime("2024-$m-28 14:00:00")) : null;
                $revId = ($status == 'approved') ? $reviewerId : null;

                // Electricity (Scope 2)
                $kwh = ($pId == 3) ? (43000 + rand(-1500, 2500)) : (59000 + rand(-2500, 3000));
                $tco2 = round(($kwh * $fElec['co2e_factor']) / 1000, 4);
                $insertGhg->execute([$pId, 2024, $m, 'Scope2', "{$pName} 外購台電用電", $fElec['id'], $kwh, '度', $tco2, "TPC-2024-0{$m}", null, $status, $revId, $revComment, $revAt, $adminId]);

                // Mobile Gasoline (Scope 1)
                $gasL = rand(250, 450);
                $tco2G = round(($gasL * $fGas['co2e_factor']) / 1000, 4);
                $insertGhg->execute([$pId, 2024, $m, 'Scope1', "{$pName} 公務車輛無鉛汽油", $fGas['id'], $gasL, '公升', $tco2G, "CPC-2024-0{$m}", null, $status, $revId, $revComment, $revAt, $adminId]);

                // Stationary Diesel (Scope 1)
                $dieL = rand(280, 520);
                $tco2D = round(($dieL * $fDiesel['co2e_factor']) / 1000, 4);
                $insertGhg->execute([$pId, 2024, $m, 'Scope1', "{$pName} 備用柴油發電機", $fDiesel['id'], $dieL, '公升', $tco2D, "OIL-2024-0{$m}", null, $status, $revId, $revComment, $revAt, $adminId]);

                // Tap water (Scope 3)
                $wM3 = rand(1100, 1900);
                $tco2W = round(($wM3 * $fWater['co2e_factor']) / 1000, 4);
                $insertGhg->execute([$pId, 2024, $m, 'Scope3', "{$pName} 廠區自來水", $fWater['id'], $wM3, '度', $tco2W, "WAT-2024-0{$m}", null, $status, $revId, $revComment, $revAt, $adminId]);

                // Waste (Scope 3)
                $kgWaste = rand(1500, 3500);
                $tco2Waste = round(($kgWaste * $fWaste['co2e_factor']) / 1000, 4);
                $insertGhg->execute([$pId, 2024, $m, 'Scope3', "{$pName} 一般事業廢棄物委外焚化", $fWaste['id'], $kgWaste, '公斤', $tco2Waste, "WST-2024-0{$m}", null, $status, $revId, $revComment, $revAt, $adminId]);
            }
        }
        echo "GHG emissions seeded.\n";
    }

    // 5. Energy and Water records
    $ewCount = $pdo->query("SELECT COUNT(*) FROM esg_energy_water")->fetchColumn();
    if ($ewCount == 0) {
        $insertEw = $pdo->prepare("
            INSERT INTO esg_energy_water 
            (org_unit_id, period_year, period_month, elec_grid_kwh, elec_green_kwh, water_tap_m3, water_recycle_m3, natural_gas_m3, diesel_liters, gasoline_liters, workflow_status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $adminId = 1;
        foreach ([3, 4] as $pId) {
            for ($y = 2023; $y <= 2024; $y++) {
                $maxM = ($y == 2023) ? 12 : 9;
                for ($m = 1; $m <= $maxM; $m++) {
                    $grid = ($pId == 3) ? (43000 + rand(-1000, 2000)) : (59000 + rand(-1500, 2500));
                    $green = ($y == 2024) ? rand(5000, 9000) : rand(1000, 3000); // Expanding green energy
                    $tap = rand(1200, 2100);
                    $recycle = rand(400, 800);
                    $ng = rand(700, 1400);
                    $ds = rand(300, 500);
                    $gs = rand(250, 450);
                    $status = ($y == 2023) ? 'locked' : (($m <= 7) ? 'approved' : 'pending');
                    $insertEw->execute([$pId, $y, $m, $grid, $green, $tap, $recycle, $ng, $ds, $gs, $status, $adminId]);
                }
            }
        }
        echo "Energy & Water records seeded.\n";
    }

    // 6. Social Metrics
    $socCount = $pdo->query("SELECT COUNT(*) FROM esg_social_metrics")->fetchColumn();
    if ($socCount == 0) {
        $insertSoc = $pdo->prepare("
            INSERT INTO esg_social_metrics
            (org_unit_id, period_year, male_employees, female_employees, disabled_employees, female_managers, total_work_hours, disabling_injury_count, lost_days_count, fr_value, sr_value, training_hours_total, volunteer_hours, donation_amount_ntd)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        // Plant 3 (2023 & 2024)
        // 2023: 180 male, 120 female, 4 disabled, 12 female mgrs, 600,000 hrs, 1 injury, 15 lost days
        $fr23 = round((1 * 1000000) / 600000, 4);
        $sr23 = round((15 * 1000000) / 600000, 4);
        $insertSoc->execute([3, 2023, 180, 120, 4, 12, 600000, 1, 15, $fr23, $sr23, 4800, 320, 250000]);

        // 2024: 185 male, 135 female, 5 disabled, 15 female mgrs, 640,000 hrs, 0 injury, 0 lost days
        $insertSoc->execute([3, 2024, 185, 135, 5, 15, 640000, 0, 0, 0.0, 0.0, 5200, 450, 360000]);

        // Plant 4 (2023 & 2024)
        $fr23_4 = round((2 * 1000000) / 820000, 4);
        $sr23_4 = round((28 * 1000000) / 820000, 4);
        $insertSoc->execute([4, 2023, 240, 160, 6, 14, 820000, 2, 28, $fr23_4, $sr23_4, 6100, 410, 300000]);
        $fr24_4 = round((1 * 1000000) / 850000, 4);
        $sr24_4 = round((6 * 1000000) / 850000, 4);
        $insertSoc->execute([4, 2024, 250, 175, 7, 18, 850000, 1, 6, $fr24_4, $sr24_4, 7200, 560, 420000]);
        echo "Social metrics seeded.\n";
    }

    // 7. Governance Metrics
    $govCount = $pdo->query("SELECT COUNT(*) FROM esg_governance_metrics")->fetchColumn();
    if ($govCount == 0) {
        $insertGov = $pdo->prepare("
            INSERT INTO esg_governance_metrics
            (period_year, board_seats_total, independent_directors, female_directors, board_meetings_count, board_attendance_rate, ethics_train_headcount, whistleblower_cases, whistleblower_closed, cyber_incidents_count, legal_penalty_count, legal_penalty_amount)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertGov->execute([2023, 9, 4, 3, 7, 96.50, 680, 2, 2, 0, 0, 0.00]);
        $insertGov->execute([2024, 9, 4, 3, 8, 98.20, 745, 1, 1, 0, 0, 0.00]);
        echo "Governance metrics seeded.\n";
    }

    // 8. Sample Audit Logs
    $auditCount = $pdo->query("SELECT COUNT(*) FROM sys_audit_logs")->fetchColumn();
    if ($auditCount == 0) {
        $insertAudit = $pdo->prepare("
            INSERT INTO sys_audit_logs (user_id, action_type, target_table, record_id, old_values, new_values, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertAudit->execute([1, 'LOGIN', 'sys_users', 1, null, json_encode(['username' => 'admin']), '127.0.0.1', 'Mozilla/5.0 System Initializer']);
        $insertAudit->execute([4, 'CREATE', 'esg_ghg_emissions', 1, null, json_encode(['emission_source' => '桃園大園一廠 外購台電用電', 'amount' => 43250]), '127.0.0.1', 'Mozilla/5.0 Chrome 128']);
        $insertAudit->execute([3, 'APPROVE', 'esg_ghg_emissions', 1, json_encode(['workflow_status' => 'pending']), json_encode(['workflow_status' => 'approved']), '127.0.0.1', 'Mozilla/5.0 Edge 128']);
        echo "Audit logs seeded.\n";
    }

    echo "All seed data populated successfully!\n";

} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
