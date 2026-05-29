-- ============================================================
-- 幸福小厨 🏠 家庭点单系统 - 数据库初始化脚本
-- PHP 8.2 + MySQL 8.0
-- ============================================================

CREATE DATABASE IF NOT EXISTS happy_kitchen
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE happy_kitchen;

-- -----------------------------------------------------------
-- 分类表：早餐、午餐、晚餐、甜品、饮品……
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50)  NOT NULL UNIQUE COMMENT '分类名称',
    icon        VARCHAR(20)  NOT NULL DEFAULT '🍽️' COMMENT 'Emoji 图标',
    sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '排序',
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='菜品分类';

-- -----------------------------------------------------------
-- 菜品表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS menu_items (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id   INT UNSIGNED NOT NULL COMMENT '所属分类',
    name          VARCHAR(100) NOT NULL COMMENT '菜名',
    price         DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT '价格（元）',
    description   VARCHAR(500) DEFAULT NULL COMMENT '描述',
    image         VARCHAR(255) DEFAULT NULL COMMENT '图片URL',
    is_recommend  TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '是否推荐',
    is_available  TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '是否可点',
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_menu_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='菜品';

-- -----------------------------------------------------------
-- 订单表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_name   VARCHAR(50)  NOT NULL COMMENT '点餐人',
    member_avatar VARCHAR(10)  NOT NULL DEFAULT '👤' COMMENT '头像 Emoji',
    notes         VARCHAR(500) DEFAULT NULL COMMENT '备注',
    total_amount  DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '总价',
    status        ENUM('pending','confirmed','done') NOT NULL DEFAULT 'pending' COMMENT '状态',
    order_date    DATE         NOT NULL DEFAULT (CURRENT_DATE) COMMENT '点餐日期',
    meal_time     VARCHAR(20)  DEFAULT NULL COMMENT '餐别：早餐/午餐/晚餐/加餐',
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单';

-- -----------------------------------------------------------
-- 订单明细表
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED NOT NULL,
    item_id     INT UNSIGNED NOT NULL,
    item_name   VARCHAR(100) NOT NULL COMMENT '下单时的菜名（冗余）',
    quantity    TINYINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price  DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT '下单时单价',
    CONSTRAINT fk_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_item FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='订单明细';

-- -----------------------------------------------------------
-- 初始数据：暖心的默认分类与菜品
-- -----------------------------------------------------------
INSERT INTO categories (name, icon, sort_order) VALUES
    ('暖心早餐', '🌅', 1),
    ('元气午餐', '☀️', 2),
    ('温馨晚餐', '🌙', 3),
    ('甜蜜甜品', '🍰', 4),
    ('健康饮品', '🧋', 5);

INSERT INTO menu_items (category_id, name, price, description, is_recommend) VALUES
    (1, '爱心三明治', 12.00, '全麦面包夹火腿生菜，满满的爱', 1),
    (1, '阳光煎蛋', 6.00, '太阳一样的煎蛋，开启美好一天', 0),
    (1, '暖胃小米粥', 8.00, '熬得稠稠的小米粥，暖胃更暖心', 1),
    (1, '蜂蜜松饼', 15.00, '现烤松饼淋上蜂蜜，甜甜蜜蜜', 0),
    (2, '番茄牛腩饭', 28.00, '炖得软烂的牛腩，酸甜开胃', 1),
    (2, '青椒肉丝面', 22.00, '家常味道，百吃不厌', 0),
    (2, '蛋炒饭', 18.00, '粒粒分明的蛋炒饭，简单幸福', 0),
    (3, '红烧排骨', 35.00, '妈妈的味道，家的温暖', 1),
    (3, '清炒时蔬', 18.00, '新鲜蔬菜，清爽健康', 0),
    (3, '番茄蛋汤', 12.00, '酸甜可口，暖心暖胃', 0),
    (4, '芒果布丁', 16.00, '丝滑口感，甜蜜入心', 1),
    (4, '红豆汤圆', 14.00, '软糯汤圆配上红豆沙', 0),
    (5, '热牛奶', 8.00, '暖暖一杯，好梦相伴', 1),
    (5, '鲜榨果汁', 12.00, '新鲜水果现榨，维C满满', 0),
    (5, '蜂蜜柠檬水', 10.00, '酸甜清爽，滋润心田', 0);
