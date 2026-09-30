# 企業 ESG 永續管理資訊系統 (ESG-SMP)
**Enterprise ESG Sustainability Management Platform**

本系統依據 **《企業 ESG 永續管理資訊系統 系統規格書 (SRS)》** 正式發布版標準開發完成，整合於 **XAMPP (Apache + PHP 8 + MySQL 8 / MariaDB)** 環境。系統遵循 **ISO 14064-1:2018**、**GHG Protocol**、**GRI Standards 2021**、**TCFD 氣候變遷相關財務揭露** 及 **台灣金管會上市櫃公司永續發展路徑圖** 規範。

---

[![Live Demo](https://img.shields.io/badge/Demo-Online%20System-brightgreen?style=for-the-badge&logo=googlechrome)](https://darksalmon-eagle-978314.hostingersite.com/wanchi3366/)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%20%7C%208.3-blue?style=for-the-badge&logo=php)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MySQL-MariaDB-orange?style=for-the-badge&logo=mysql)](https://mariadb.org/)
[![Standards](https://img.shields.io/badge/Standards-ISO%2014064%20%7C%20GRI%202021-success?style=for-the-badge)](https://www.globalreporting.org/)

---

## 🌐 專屬系統線上運行與體驗

本系統已於雲端正式部署上線，提供完整資料庫與前後端即時互動環境，免安裝即可直接透過瀏覽器公開瀏覽與操作：

- **🚀 專屬線上系統入口**：👉 **[https://darksalmon-eagle-978314.hostingersite.com/wanchi3366/](https://darksalmon-eagle-978314.hostingersite.com/wanchi3366/)**
- **💻 本機開發環境**：`http://localhost/ESG/`
- **📦 GitHub 開源倉庫**：[https://github.com/wanchihuang-hash/esg](https://github.com/wanchihuang-hash/esg)

### 系統預設測試帳號清單 (RBAC 五大權限角色)
登入系統首頁後，可使用上方**角色切換快捷按鈕**或以下預設帳號登入體驗不同業務情境：

| 角色名稱 (Role) | 登入帳號 (Username) | 預設密碼 (Password) | 所屬單位 | 核心職責與操作權限 |
| :--- | :--- | :--- | :--- | :--- |
| **系統管理員 (Super Admin)** | `admin` | `admin123` | 綠能永續集團總部 | 全系統最高權限、全域參數動態配置、組織架構管理、帳號角色分配、係數庫維護、全生命週期稽核日誌調閱 |
| **ESG 委員會負責人 (ESG Lead)** | `lead` | `lead123` | 綠能永續集團總部 | 綜覽全集團 ESG 戰情室、年度減碳目標監控、審查跨廠區彙總數據、產製 GRI 報告書、年度鎖檔封存 |
| **廠區審核主管 (Plant Reviewer)** | `reviewer` | `reviewer123` | 桃園大園一廠 | 線上審核所屬廠區提交之月度活動數據與佐證發票憑單，執行核准 (Approve)、退回補正 (Reject)、能資源月報審定 |
| **基層數據填報員 (Data Collector)** | `collector` | `collector123` | 桃園大園一廠 | 每月輸入水電度數、油量公升數等原始活動數據、上傳佐證單據掃描檔、即時碳排核算、提交審核 (Submit) |
| **外部查證稽核員 (Lead Auditor)** | `auditor` | `auditor123` | 獨立查證機構 (KPMG/BSI) | 唯讀查閱權限、調閱 ISO 14064 計算公式、核對原始憑單、調閱全生命週期 Audit Log 異動軌跡與 JSON Diff |

---

## 核心功能模組

### 1. ESG 永續戰情室 (Executive Dashboard & BI)
- **總量與範疇監控**：即時呈現集團總碳排 ($tCO_2e$)、範疇一/二/三佔比圓餅圖、歷年月度堆疊趨勢圖。
- **能資源轉型效率**：綠電使用比率 (%)、製程水循環回收率 (%)。
- **年度減碳 KPI 進度條**：以基準年（2023年）為基準，動態追蹤年度減碳 -5% 達成率。
- **數據異常波動預警機制**：活動數據若單月超過歷史均值 $\pm 20\%$，即時於戰情室橫幅跳出警示，防範人為填報誤植。

### 2. 環境面向 (E - Environmental)
- **溫室氣體活動數據盤查清冊**：
  - 支援範疇一直接排放（柴油發電機、天然氣鍋爐、公務車汽油、冷媒逸散）。
  - 支援範疇二能源間接（外購台電電力）。
  - 支援範疇三其他間接（自來水碳足跡、一般事業廢棄物委外焚化）。
  - **即時公式計算預覽**：填報時即時依據 $E = \frac{A \times EF}{1000}$ 動態試算 $tCO_2e$ 並呈現計算歷程。
  - **佐證附件上傳**：支援 PDF、圖片、Excel 單據上傳與在線調閱。
  - **四階段審批工作流**：`Draft (草稿)` $\rightarrow$ `Pending (待審)` $\rightarrow$ `Approved (核准)` / `Rejected (退回)` $\rightarrow$ `Locked (鎖檔)`。
- **能資源與水消耗月報**：
  - 市電 (度)、綠電 (度)、自來水 (度)、循環回收水 (度)、天然氣、柴油、汽油。
  - 自動換算熱值 (GJ) 與循環利用率。
- **排放係數庫管理**：
  - 內建環境部氣候變遷署 6.0.4 係數表、能源署 2024 公告電力排碳係數 (0.495 kgCO2e/度)。
  - 支援新增自訂係數與線上係數試算模擬器。

### 3. 社會責任 (S - Social)
- **人力資本與 DEI 多元共融**：
  - 性別比例、女性主管人數與佔比、身心障礙同仁法定進用檢核。
- **職業安全健康 (ISO 45001 / GRI 403)**：
  - 總工作時數、失能傷害次數、損失工作日數。
  - 系統自動以百萬工時標準計算：
    - 失能傷害頻率 \(FR = \frac{\text{失能次數} \times 1,000,000}{\text{總工時}}\)
    - 失能傷害嚴重率 \(SR = \frac{\text{損失日數} \times 1,000,000}{\text{總工時}}\)
- **培訓發展與社會參與**：
  - 員工培訓總時數、志工服務時數、公益捐贈金額。

### 4. 公司治理 (G - Governance)
- **董事會運作效能**：
  - 董事席位、獨立董事比例、女性董事比例、開會次數、平均出席率。
- **誠信經營與反貪腐 (GRI 205)**：
  - 誠信經營受訓人次、獨立舉報信箱受理與結案進度追蹤。
- **資安與合規防護**：
  - 重大資安事件通報、環保/勞動法規裁罰與罰鍰金額監控。
- **TCFD 氣候風險矩陣**：
  - 實體風險（淹水、暴雨）與轉型風險（碳費徵收、RE100 要求）之衝擊度、發生機率與因應對策。

### 5. 永續揭露與查證 (GRI Reporting & Disclosures)
- **GRI Standards 2021 內容索引表**：
  - 自動聚合通用準則 (GRI 2, GRI 3) 與主題準則 (GRI 205, GRI 302, GRI 303, GRI 305, GRI 403, GRI 404, GRI 405) 之定量數據。
- **一鍵匯出**：匯出符合金管會申報格式之 CSV/Excel 活動數據清冊。
- **報表列印 / PDF**：內建專屬 Print CSS 樣式，可直接一鍵另存為第三方查證稽核底稿 PDF。
- **年度審定封存鎖檔 (Data Locking)**：經第三方查證後執行鎖檔，鎖定後歷史數據不可隨意竄改，符合稽核合規性。

### 6. 全生命週期稽核軌跡 (Audit Trail)
- 記錄使用者登入、新增、修改、刪除、審批、退回、鎖檔與匯出。
- 完整留存異動前 (Old Values) 與異動後 (New Values) 之 JSON 差異比對、操作 IP 及時間戳記。

---

## RESTful API 規格

| HTTP 方法 | API 路由 | 說明 |
| :--- | :--- | :--- |
| `GET` | `index.php?route=api/dashboard-summary&year=2024` | 取得戰情室總碳排、範疇佔比與能資源效率 JSON |
| `GET/POST` | `index.php?route=api/calculate&factor_id=1&amount=1000` | 線上碳排即時核算，回傳計算當量、公式與異常警示 |
| `GET` | `index.php?route=api/factor-info&id=1` | 取得特定排放係數詳細資料 |
| `GET` | `index.php?route=api/emissions&year=2024` | 取得溫室氣體活動數據清冊 JSON |
| `GET` | `index.php?route=report_export&year=2024` | 下載盤查清冊 CSV 檔案 |

---

## 系統架構目錄

```
c:/xampp/htdocs/ESG/
├── app/
│   ├── Controllers/          # 業務控制器 (Auth, Dashboard, Ghg, Social, Governance, Report 等)
│   ├── Audit.php             # 稽核日誌引擎 (Audit Trail)
│   ├── Auth.php              # RBAC 身份驗證與授權核心
│   ├── Database.php          # PDO 單例連線封裝 (Prepared Statements 防注入)
│   └── GhgEngine.php         # 碳排核算引擎 (ISO 14064-1 & ±20% 異常檢測)
├── assets/
│   ├── css/style.css         # 企業永續綠 (#1E4620) 專屬品牌樣式
│   └── js/                   # 前端即時試算與圖表腳本
├── config/
│   ├── app.php               # 應用程式設定、CSRF Token 與 Session 管理
│   └── database.php          # MySQL 資料庫連線配置
├── database/
│   └── seed.php              # 資料庫初始化種子資料建立腳本
├── tests/
│   └── system_test.php       # 全系統自動化單元與整合驗收測試
├── uploads/                  # 佐證單據與發票上傳安全目錄
├── views/                    # MVC 視圖樣板 (Dashboard, GHG, Energy, Social, Governance, GRI, Audit)
├── index.php                 # 單一入口路由 (Front Controller)
└── .htaccess                 # Apache 偽靜態與安全性設定
```
