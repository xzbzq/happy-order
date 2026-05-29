# 🏠 幸福小厨 · 家庭点单系统

> 一个暖心、可爱的家庭点单系统，让家人点餐像发朋友圈一样简单。  
> **PHP 8.2 + MySQL 8.0 + Vue 3 + GSAP**

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)
![Vue](https://img.shields.io/badge/Vue-3-4FC08D?style=flat&logo=vue.js&logoColor=white)
![GSAP](https://img.shields.io/badge/GSAP-3.12-88CE02?style=flat&logo=greensock&logoColor=white)

---

## 📖 项目简介

幸福小厨是一个为家庭场景设计的点单系统。不同于外卖平台的复杂流程，它专注于**家庭内部**的温馨点餐体验：

- 🏠 家人打开页面就能看到今日菜单，点点手机就能下单
- 👨‍👩‍👧‍👦 支持多个家庭成员（爸爸、妈妈、爷爷、奶奶、宝贝）
- 🕐 区分早餐、午餐、晚餐、加餐
- 📋 首页实时展示今日订单，一目了然
- 🔐 管理模式可锁定，管理员密码保护

整体设计采用温暖的橙色调、玻璃拟态卡片和 GSAP 动效，营造"家的味道"。

---

## ✨ 功能全景

### 🏠 首页
| 模块 | 说明 |
|------|------|
| 英雄区 | 今日点单数、推荐菜品数、家庭成员数，一键点单 |
| 三餐状态条 | 早餐/午餐/晚餐/加餐分别显示已点数量 |
| 今日订单 | 实时列出的订单，可确认完成 / 取消 |
| 今日推荐 | 推荐菜品翻页展示（每页 7 道），左右切换 |
| 温馨小贴士 | 随机展示暖心小提示，可刷新换一条 |
| 温暖页脚 | 动画飘浮装饰 + 家庭格言，一直在动 |

### 🍳 点单页
- 左侧分类导航（早餐/午餐/晚餐/甜品/饮品等）
- 右侧菜品网格，推荐菜品带 ⭐ 标记
- 选择家庭成员 → 选择餐别 → 挑选菜品加入购物车
- 购物车实时汇总，支持增减数量、删除
- 提交订单后自动发送邮件通知（需配置 SMTP）

### 📋 菜单管理
- 分类管理：新增、编辑、删除（带 emoji 选择器）
- 菜品管理：新增、编辑、删除（名称、价格、描述、推荐、上架）
- 管理模式：密码锁定/解锁（动画弹跳反馈）
- 批量导入：一键导入数十道预设菜品
- 空状态彩蛋：双击触发五彩纸屑 🎊

### 📅 历史订单
- 按日期筛选订单
- 查看每单详情（点餐人、菜品、数量、总价、备注）

### 🔐 后台管理面板
独立管理后台 `admin/`：
- 仪表盘：今日订单数、本月订单数、总菜品数、总分类数、近 7 日趋势
- 订单管理：按日期/状态/成员筛选，确认完成，删除
- 菜品管理：完整 CRUD，推荐标记
- 分类管理：完整 CRUD
- 系统设置：修改密码、SMTP 邮件配置、测试邮件

---

## 🎬 动效特色

| 场景 | 效果 |
|------|------|
| 页面切换 | 交错入场 + 弹性缩放的卡片动画 |
| 管理模式 | 锁按钮弹跳 (back.out 缓动) |
| Logo 彩蛋 | 连击 5 次 → 旋转 + 金色光粒子飘散 |
| 空状态 | 双击触发五彩纸屑 🎊 |
| 下单成功 | 多彩纸屑庆祝 🎉 |
| 首页入场 | GSAP Timeline 顺序编排：英雄区 → 三餐 → 双列内容 |
| 菜单页入场 | 分类列表 + 菜品卡片错开浮现 |
| 页脚 | 飘浮装饰 + 呼吸文字 + 脉动分隔线（纯 CSS 持续动画） |
| 减少动效 | 全局识别 `prefers-reduced-motion`，CSS + JS 双重重度降级 |

---

## 🧱 技术架构

```
┌─────────────────────────────────────────────┐
│             浏览器 (Vue 3 SPA)               │
│  index.php + app.js + style.css + GSAP 3.12 │
└──────────────┬──────────────────────────────┘
               │ RESTful JSON API
┌──────────────▼──────────────────────────────┐
│              PHP 8.2 (PDO)                   │
│  api/categories.php  api/items.php           │
│  api/orders.php      api/admin.php           │
│  api/license.php     api/seed.php            │
└──────────────┬──────────────────────────────┘
               │ utf8mb4
┌──────────────▼──────────────────────────────┐
│              MySQL 8.0                       │
│  happy_kitchen 数据库                        │
│  categories / menu_items / orders / order_items│
└─────────────────────────────────────────────┘
```

### 前端
- **框架**: Vue 3 (CDN 全局构建) — `createApp` + `setup()` API
- **动画**: GSAP 3.12.5 (CDN) — Timeline, stagger, back.out 缓动
- **样式**: 原生 CSS — 玻璃拟态、CSS 变量、emojis 字体栈
- **路由**: 无 vue-router，手动 `currentView` ref 控制视图切换
- **HTTP**: `fetch` + AbortController (15 秒超时)

### 后端
- **语言**: PHP 8.2
- **数据库**: PDO (MySQL 8.0) — 预处理语句、utf8mb4
- **认证**: Session 基础管理员认证、bcrypt 密码哈希
- **邮件**: 原生 PHP Socket SMTP 发送（可配置）

### 安全
- 管理员密码 bcrypt 哈希存储
- 写操作 API 要求管理员 session
- PDO 预处理语句防注入
- Nginx 配置屏蔽 `config/` 和 `sql/` 目录
- `.htaccess` 限制直接目录访问

---

## 🚀 部署指南

### 环境要求

| 组件 | 版本要求 |
|------|---------|
| PHP | ≥ 8.0（推荐 8.2）|
| MySQL | ≥ 5.7（推荐 8.0）|
| PHP 扩展 | pdo_mysql, json, session |
| Web 服务器 | Nginx 1.25+ 或 Apache 2.4+ |

### 方案 A：PHP 内置服务器（开发测试）

```bash
# 克隆项目
git clone https://github.com/your-username/happy-order.git
cd happy-order

# 启动开发服务器
php -S 0.0.0.0:8080 -t .
```

浏览器访问 `http://localhost:8080/`，自动进入安装向导。

### 方案 B：Nginx + PHP-FPM（生产推荐）

**1. 部署文件**

将项目文件上传到服务器目录（如 `/var/www/happy-order`）。

**2. 配置 Nginx**

```bash
# 复制配置
sudo cp happy-order.nginx.conf /etc/nginx/sites-available/happy-order

# 编辑配置，修改 root 路径
sudo vim /etc/nginx/sites-available/happy-order

# 启用站点
sudo ln -s /etc/nginx/sites-available/happy-order /etc/nginx/sites-enabled/

# 检查并重载
sudo nginx -t && sudo systemctl reload nginx
```

**3. 设置目录权限**

```bash
sudo chown -R www-data:www-data /var/www/happy-order/config
sudo chmod -R 755 /var/www/happy-order
```

**4. 访问安装向导**

浏览器打开 `http://your-domain/` 按步骤完成安装。

### 方案 C：Apache

确保已启用 `mod_rewrite`，项目自带的 `.htaccess` 会处理 URL 重写。

---

## 🔧 安装向导

首次访问自动跳转 `install.php`，全程可视化：

```
Step 1  🏠 环境检测    → PHP 版本、PDO 扩展、目录权限
Step 2  🔌 数据库配置   → 填写 MySQL 连接信息，测试连接
Step 3  ⚙️  自动安装    → 写入配置、建库建表、导入初始菜单
Step 4  🎉 安装完成     → 进入系统
```

全程无需手动编辑任何文件。

---

## 📡 API 文档

所有 API 端点返回 JSON，公共基础路径为 `/api/`。

### 通用格式

```json
// 成功
{ "id": 1, "name": "早餐", ... }

// 错误
{ "error": "错误信息" }
```

### 分类 API — `api/categories.php`

| 方法 | 说明 | 需要管理员 |
|------|------|----------|
| `GET` | 获取全部分类（按 sort_order 排序） | ❌ |
| `POST` | 新增分类：`{ name, icon, sort_order }` | ✅ |
| `PUT` | 修改分类：`{ id, name, icon, sort_order }` | ✅ |
| `DELETE` | 删除分类：`?id=` | ✅ |

### 菜品 API — `api/items.php`

| 方法 | 说明 | 需要管理员 |
|------|------|----------|
| `GET` | 获取菜品列表，可选 `?category_id=` `?available=` | ❌ |
| `POST` | 新增菜品：`{ category_id, name, price, description, is_recommend, is_available }` | ✅ |
| `PUT` | 修改菜品：`{ id, name, price, ... }` | ✅ |
| `DELETE` | 删除菜品：`?id=` | ✅ |

### 订单 API — `api/orders.php`

| 方法 | 说明 | 需要管理员 |
|------|------|----------|
| `GET` | 获取订单：`?date=YYYY-MM-DD` 或 `?id=` | ❌ |
| `POST` | 创建订单：`{ member_name, member_avatar, items[], meal_time, notes }` | ❌ |
| `PUT` | 更新订单状态：`{ id, status }` | ✅ |
| `DELETE` | 删除订单：`?id=` | ✅ |

创建订单成功后自动发送 SMTP 邮件通知（若已配置）。

### 管理 API — `api/admin.php`

通过 `?action=` 参数分发：

| Action | 方法 | 说明 |
|--------|------|------|
| `login` | POST | 管理员登录（首次自动创建密码） |
| `logout` | GET | 退出登录 |
| `status` | GET | 检查登录状态 |
| `change_password` | PUT | 修改密码 |
| `stats` | GET | 仪表盘统计数据 |
| `orders` | GET | 筛选订单列表 |
| `update_order` | PUT | 更新订单状态 |
| `delete_order` | GET | 删除订单 |
| `categories_with_count` | GET | 分类列表（含菜品数） |
| `email_config` | GET | 读取邮件配置 |
| `save_email_config` | PUT | 保存邮件配置 |
| `test_email` | POST | 发送测试邮件 |

### 数据导入 API — `api/seed.php`

| 方法 | 说明 | 需要管理员 |
|------|------|----------|
| `POST` | 一键导入 8 分类 + 40+ 道预设菜品 | ✅ |

---

## 🗄️ 数据库结构

### `categories` — 菜品分类

| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT UNSIGNED AI | 主键 |
| name | VARCHAR(50) UNIQUE | 分类名称 |
| icon | VARCHAR(20) | Emoji 图标 |
| sort_order | TINYINT UNSIGNED | 排序号 |

### `menu_items` — 菜品

| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT UNSIGNED AI | 主键 |
| category_id | INT UNSIGNED FK | 所属分类 → categories(id) CASCADE |
| name | VARCHAR(100) | 菜名 |
| price | DECIMAL(8,2) | 价格（元）|
| description | VARCHAR(500) | 描述 |
| is_recommend | TINYINT(1) | ⭐ 是否推荐 |
| is_available | TINYINT(1) | 是否上架 |

### `orders` — 订单

| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT UNSIGNED AI | 主键 |
| member_name | VARCHAR(50) | 点餐人 |
| member_avatar | VARCHAR(10) | 头像 Emoji |
| total_amount | DECIMAL(10,2) | 总价 |
| status | ENUM('pending','confirmed','done') | 订单状态 |
| order_date | DATE | 点餐日期 |
| meal_time | VARCHAR(20) | 餐别 |
| notes | VARCHAR(500) | 备注 |

### `order_items` — 订单明细

| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT UNSIGNED AI | 主键 |
| order_id | INT UNSIGNED FK | → orders(id) CASCADE |
| item_id | INT UNSIGNED FK | → menu_items(id) CASCADE |
| item_name | VARCHAR(100) | 冗余菜名 |
| quantity | TINYINT UNSIGNED | 数量 |
| unit_price | DECIMAL(8,2) | 单价 |

---

## 📁 目录结构

```
happy-order/
├── index.php                 # SPA 主入口（Vue 3 外壳）
├── install.php               # 安装向导（首次访问自动跳转）
├── diagnose.php              # 系统诊断工具
├── license.php               # 授权激活页面
│
├── config/
│   ├── database.php          # 数据库配置（安装向导生成）
│   ├── admin.php             # 管理员密码哈希（首次登录生成）
│   ├── email.php             # SMTP 邮件配置（后台保存）
│   └── license.php           # 授权信息（激活生成）
│
├── sql/
│   └── schema.sql            # 建表脚本 + 初始数据
│
├── lib/
│   ├── License.php           # 授权验证系统
│   └── Mailer.php            # SMTP 邮件发送
│
├── admin/
│   ├── index.php             # 管理后台仪表盘
│   ├── login.php             # 管理后台登录
│   └── logout.php            # 退出登录
│
├── api/
│   ├── categories.php        # 分类 CRUD
│   ├── items.php             # 菜品 CRUD
│   ├── orders.php            # 订单 CRUD + 邮件通知
│   ├── admin.php             # 管理后台 API
│   ├── license.php           # 授权 API
│   └── seed.php              # 批量导入
│
├── assets/
│   ├── css/
│   │   └── style.css         # 全部样式（玻璃拟态、暖色调）
│   └── js/
│       └── app.js            # Vue 3 + GSAP 动画
│
├── happy-order.nginx.conf    # Nginx 配置模板
├── .htaccess                 # Apache URL 重写
└── .gitignore
```

---

## 📦 依赖

| 依赖 | 版本 | 加载方式 |
|------|------|---------|
| Vue | 3.x | CDN (`unpkg.com/vue@3`) |
| GSAP | 3.12.5 | CDN (`cdnjs.cloudflare.com/gsap`) |

项目无任何后端 Composer 依赖，**零外部 PHP 包**，开箱即用。

---

## 👨‍💻 开发者指南

### 本地开发

```bash
# 启动 PHP 内置服务器
php -S 0.0.0.0:8080 -t .

# 修改 assets/js/app.js 或 assets/css/style.css 后刷新即可
# 修改 API 文件后立即生效，无需编译
```

### 前端架构要点

- `app.js` 是 Vue 3 SPA 全部逻辑所在
- 视图切换通过 `currentView` ref + `v-show` 控制
- GSAP 动画统一在 `runHomeAnim()`, `runOrderAnim()`, `runMenuAnim()`, `runHistoryAnim()` 中
- 所有 API 调用通过 `api()` 函数封装（自动处理 401、超时、错误提示）
- 减少动效偏好通过 `prefersMotion()` 函数 + CSS `@media (prefers-reduced-motion)` 双重保障

### 后端架构要点

- 数据库连接统一通过 `config/database.php` 中的 `getDB()` 工具函数
- JSON 响应通过 `jsonExit()` 工具函数
- 管理员认证依赖 `$_SESSION['admin_logged_in']`
- API 层与表现层完全分离（无 HTML 混合输出）

---

## ❤️ 设计理念

- **温暖的橙色调** — 主色 `#E8833A`，搭配米白背景，营造家庭厨房的温馨感
- **玻璃拟态卡片** — `backdrop-filter: blur(16px)`，柔和通透
- **Emoji 贯穿全程** — 分类、按钮、空状态都有对应 emoji，降低认知成本
- **GSAP 动画** — 弹性缓动 (back.out) + 交错入场 (stagger)，让界面有"呼吸感"
- **无障碍** — `:focus-visible` 键盘焦点、`aria-label` 图标按钮、屏幕减少动效支持

---

## 🔐 授权

本项目采用自主授权系统。详细信息请查看 `license.php` 及 `lib/License.php`。

---

## 📄 说明

本项目发布于个人工作室，供学习交流使用。  
感谢使用 ❤️
