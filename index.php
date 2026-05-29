<?php
/**
 * 幸福小厨 🏠 家庭点单系统 v2.0
 * 主入口 — 顶部导航 · 精致暖系
 */
$configFile = __DIR__ . '/config/database.php';
if (!file_exists($configFile) || filesize($configFile) < 100) {
    header('Location: install.php');
    exit;
}
require $configFile;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>🏠 幸福小厨 · 家庭点单</title>
    <link rel="stylesheet" href="assets/css/style.css?v=2.0">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js">
    </script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js">
    </script>
</head>
<body>
    <div id="app">

        <!-- ====== 顶部导航 ====== -->
        <header class="top-nav" ref="topNav">
            <div class="nav-inner">
                <div class="nav-left" @click="handleLogoClick">
                    <span class="nav-logo-icon">🏠</span>
                    <span class="nav-logo-text">幸福小厨</span>
                </div>

                <nav class="nav-center" ref="navCenter">
                    <a v-for="nav in navs" :key="nav.id"
                       :class="['nav-btn', { active: currentView === nav.id }]"
                       @click="switchView(nav.id)">
                        <span class="nav-btn-icon">{{ nav.icon }}</span>
                        <span class="nav-btn-label">{{ nav.label }}</span>
                    </a>
                </nav>

                <div class="nav-right">
                    <span class="nav-date">{{ todayStr }}</span>
                    <a href="admin/login.php" class="nav-admin-link" target="_blank" title="后台管理">⚙️</a>
                </div>
            </div>
            <!-- 导航底部进度条彩蛋 -->
            <div class="nav-progress" ref="navProgress"></div>
        </header>

        <!-- ====== 移动端底部 Tab ====== -->
        <nav class="bottom-tab" ref="bottomTab">
            <a v-for="nav in navs" :key="nav.id"
               :class="['tab-item', { active: currentView === nav.id }]"
               @click="switchView(nav.id)">
                <span class="tab-icon">{{ nav.icon }}</span>
                <span class="tab-label">{{ nav.label }}</span>
            </a>
        </nav>

        <!-- ====== 主内容区 ====== -->
        <main class="main-area" ref="mainArea">
            <div class="content-scroll" ref="contentScroll">

                <!-- ====== 首页 ====== -->
                <div v-show="currentView === 'home'" class="page-view" ref="homePage">

                    <!-- 加载骨架 -->
                    <div v-if="dataLoading" class="loading-skeleton">
                        <div class="skeleton-block" style="height:200px;border-radius:24px;"></div>
                        <div style="display:flex;gap:10px;margin-top:16px;">
                            <div class="skeleton-block" style="flex:1;height:50px;border-radius:14px;"></div>
                            <div class="skeleton-block" style="flex:1;height:50px;border-radius:14px;"></div>
                            <div class="skeleton-block" style="flex:1;height:50px;border-radius:14px;"></div>
                            <div class="skeleton-block" style="flex:1;height:50px;border-radius:14px;"></div>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 280px;gap:20px;margin-top:20px;">
                            <div class="skeleton-block" style="height:300px;border-radius:16px;"></div>
                            <div style="display:flex;flex-direction:column;gap:20px;">
                                <div class="skeleton-block" style="height:200px;border-radius:16px;"></div>
                                <div class="skeleton-block" style="height:120px;border-radius:16px;"></div>
                            </div>
                        </div>
                    </div>

                    <template v-if="!dataLoading">

                    <!-- 英雄区 -->
                    <section class="hero" ref="hero">
                        <div class="hero-deco">
                            <span class="hero-float" ref="hf1">🍳</span>
                            <span class="hero-float" ref="hf2">🍤</span>
                            <span class="hero-float" ref="hf3">🍜</span>
                            <span class="hero-float" ref="hf4">🍩</span>
                            <span class="hero-float" ref="hf5">🍝</span>
                        </div>
                        <div class="hero-body">
                            <div class="hero-text">
                                <h2 ref="heroTitle">🌞 今日好食光</h2>
                                <p ref="heroSub">为家人做一顿可口的饭菜，是最幸福的事</p>
                            </div>
                            <div class="hero-metrics" ref="heroMetrics">
                                <div class="metric">
                                    <span class="metric-icon">📋</span>
                                    <div><strong class="metric-val">{{ todayOrders.length }}</strong><span class="metric-lbl">今日订单</span></div>
                                </div>
                                <div class="metric-divider"></div>
                                <div class="metric">
                                    <span class="metric-icon">💰</span>
                                    <div><strong class="metric-val">¥{{ todayRevenue }}</strong><span class="metric-lbl">今日消费</span></div>
                                </div>
                                <div class="metric-divider"></div>
                                <div class="metric">
                                    <span class="metric-icon">👨‍👩‍👧‍👦</span>
                                    <div><strong class="metric-val">{{ todayMembers }}</strong><span class="metric-lbl">就餐人数</span></div>
                                </div>
                            </div>
                            <div class="hero-actions" ref="heroActions">
                                <button class="btn btn-primary btn-glow" @click="switchView('order')">🍳 开始点单</button>
                                <button class="btn btn-ghost" @click="switchView('menu')">📋 管理菜单</button>
                                <a href="admin/login.php" class="btn btn-ghost" target="_blank">⚙️ 后台</a>
                            </div>
                        </div>
                    </section>

                    <!-- 三餐状态 -->
                    <section class="meal-strip" ref="mealStrip">
                        <div v-for="m in mealTimes" :key="m" class="meal-pill">
                            <span class="meal-pill-icon">{{ mealIcons[m] }}</span>
                            <span class="meal-pill-name">{{ m }}</span>
                            <span class="meal-pill-count" :class="{ has: mealOrderCounts[m] > 0 }">
                                {{ mealOrderCounts[m] || 0 }}
                            </span>
                        </div>
                    </section>

                    <!-- 双列内容 -->
                    <div class="home-grid">
                        <div class="home-main">
                            <div class="card glass" ref="todayCard">
                                <div class="card-head">
                                    <h3>📋 今日订单</h3>
                                    <div class="card-head-right">
                                        <span class="chip chip-primary" v-if="todayOrders.length > 0">{{ todayOrders.length }} 单</span>
                                        <button class="btn-txt" @click="switchView('history')">查看全部 →</button>
                                    </div>
                                </div>

                                <div v-if="todayOrders.length === 0" class="empty" @dblclick="handleEmptyDblClick">
                                    <div class="empty-icon">🍃</div>
                                    <p class="empty-t">今天还没有人点单呢 ~</p>
                                    <p class="empty-d">让家人来点些好吃的吧 🥰</p>
                                    <button class="btn btn-primary btn-sm" @click="switchView('order')">🍳 去点单</button>
                                </div>

                                <div v-else class="order-list">
                                    <div v-for="o in todayOrders" :key="o.id"
                                         :class="['order-item', 'order-' + o.status]"
                                         ref="orderCards">
                                        <div class="oi-head">
                                            <div class="oi-user">
                                                <span class="oi-avatar" :style="{ background: memberColors[o.member_name] || '#f5e6d0' }">{{ o.member_avatar }}</span>
                                                <div>
                                                    <strong>{{ o.member_name }}</strong>
                                                    <span class="oi-meal">{{ o.meal_time || '随便吃吃' }}</span>
                                                </div>
                                            </div>
                                            <span :class="['status-badge', o.status]">{{ statusMap[o.status] }}</span>
                                        </div>
                                        <div class="oi-items">
                                            <span v-for="(oi, idx) in o.items" :key="idx" class="oi-chip">
                                                {{ oi.item_name }}<small v-if="oi.quantity > 1">×{{ oi.quantity }}</small>
                                            </span>
                                        </div>
                                        <div class="oi-foot">
                                            <span class="oi-total">💰 {{ o.total_amount }} 元</span>
                                            <span class="oi-time" v-if="o.created_at">🕐 {{ formatTime(o.created_at) }}</span>
                                            <div class="oi-acts">
                                                <button v-if="o.status === 'pending'" class="btn btn-xs btn-success" @click="updateOrderStatus(o.id, 'confirmed')" aria-label="确认订单">✅ 确认</button>
                                                <button v-if="o.status === 'confirmed'" class="btn btn-xs btn-primary" @click="updateOrderStatus(o.id, 'done')" aria-label="标记完成">🎉 完成</button>
                                                <button class="btn btn-xs btn-soft" @click="deleteOrder(o.id)" aria-label="取消订单">🗑️</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="home-side">
                            <div class="card glass" ref="recCard">
                                <div class="card-head">
                                    <h3>⭐ 今日推荐</h3>
                                    <span class="chip chip-warning" v-if="recommendItems.length > 0">{{ recommendItems.length }} 道</span>
                                </div>
                                <div v-if="recommendItems.length === 0" class="empty-sm"><p>还没有推荐菜品~</p></div>
                                <div v-else class="rec-stack">
                                    <div v-for="item in pagedRecItems" :key="item.id" class="rec-row" ref="recRows">
                                        <span class="rec-icon">{{ item.category_icon }}</span>
                                        <div class="rec-info">
                                            <strong>{{ item.name }}</strong>
                                            <span class="rec-desc">{{ item.description }}</span>
                                        </div>
                                        <span class="rec-price">¥{{ item.price }}</span>
                                    </div>
                                    <!-- 翻页 -->
                                    <div class="rec-nav" v-if="recPageTotal > 1">
                                        <button class="rec-nav-btn" @click="prevRecPage" :disabled="recPage === 0">‹</button>
                                        <span class="rec-dots">
                                            <span v-for="i in recPageTotal" :key="i"
                                                  :class="['rec-dot', { active: i - 1 === recPage }]"></span>
                                        </span>
                                        <button class="rec-nav-btn" @click="nextRecPage" :disabled="recPage >= recPageTotal - 1">›</button>
                                    </div>
                                </div>
                            </div>

                            <div class="card glass tip-card" ref="tipCard">
                                <div class="tip-body">
                                    <span class="tip-emoji">💡</span>
                                    <div>
                                        <strong>{{ randomTip.title }}</strong>
                                        <p>{{ randomTip.content }}</p>
                                    </div>
                                </div>
                                <button class="tip-btn" @click="refreshTip" title="换一个">🔄</button>
                            </div>
                        </div>
                    </div>
                </template>
                </div>

                <!-- ====== 菜单管理 ====== -->
                <div v-show="currentView === 'menu'" class="page-view" ref="menuPage">
                    <div class="card glass">
                        <div class="card-head">
                            <h3>📂 分类管理</h3>
                            <div class="card-head-right">
                                <span class="chip chip-primary">{{ categories.length }} 个</span>
                                <button v-if="!menuLocked" class="btn btn-xs btn-outline" @click="seedMenu" :disabled="seeding">
                                    {{ seeding ? '⏳' : '📥' }} 示例
                                </button>
                                <button class="btn btn-primary btn-sm" @click="showCategoryModal = true" :disabled="menuLocked">+ 新增</button>
                            </div>
                        </div>
                        <div class="cat-strip">
                            <div v-for="cat in categories" :key="cat.id" class="cat-tag" ref="catTags">
                                <span>{{ cat.icon }} {{ cat.name }}</span>
                                <button v-if="!menuLocked" class="btn-icon" @click="deleteCategory(cat.id)" title="删除">✕</button>
                            </div>
                            <div v-if="categories.length === 0" class="empty-sm"><p>还没有分类~</p></div>
                        </div>
                    </div>

                    <div class="card glass">
                        <div class="card-head">
                            <h3>🥘 菜品管理</h3>
                            <div class="card-head-right">
                                <span class="chip" :class="allItems.length > 0 ? 'chip-primary' : ''">{{ allItems.length }} 道</span>
                                <button class="btn btn-sm btn-outline btn-lock" :class="{ unlocked: !menuLocked }"
                                        @click="menuLocked ? showPasswordModal = true : lockMenu()"
                                        :title="menuLocked ? '点击解锁管理' : '点击锁定管理'">
                                    {{ menuLocked ? '🔒 管理' : '🔓 管理' }}
                                </button>
                                <button class="btn btn-primary btn-sm" @click="openAddItemModal()" :disabled="menuLocked">+ 新增</button>
                            </div>
                        </div>
                        <div v-if="allItems.length === 0" class="empty" @dblclick="handleEmptyDblClick">
                            <p>还没有菜品，快来添加吧 🧑‍🍳</p>
                        </div>
                        <div v-else class="table-wrap">
                            <table class="item-table">
                                <thead><tr>
                                    <th>分类</th><th>菜名</th><th>价格</th><th>描述</th><th>推荐</th><th>状态</th><th v-if="!menuLocked">操作</th>
                                </tr></thead>
                                <tbody>
                                    <tr v-for="item in allItems" :key="item.id" ref="itemRows">
                                        <td>{{ item.category_icon }} {{ item.category_name }}</td>
                                        <td><strong>{{ item.name }}</strong></td>
                                        <td>¥{{ item.price }}</td>
                                        <td class="desc-cell">{{ item.description || '-' }}</td>
                                        <td><span class="chip" :class="{ 'chip-warning': item.is_recommend }">{{ item.is_recommend ? '⭐ 推荐' : '—' }}</span></td>
                                        <td><span class="chip" :class="item.is_available ? 'chip-success' : ''">{{ item.is_available ? '🟢 可点' : '🔴 停售' }}</span></td>
                                        <td v-if="!menuLocked">
                                            <button class="btn btn-xs btn-outline" @click="openEditItemModal(item)" aria-label="编辑菜品">✏️</button>
                                            <button class="btn btn-xs btn-soft" @click="deleteItem(item.id)" aria-label="删除菜品">🗑️</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ====== 点单 ====== -->
                <div v-show="currentView === 'order'" class="page-view" ref="orderPage">
                    <div class="order-layout">
                        <div class="order-menu-col">
                            <div class="card glass">
                                <div class="card-head">
                                    <h3>🥗 选菜品</h3>
                                    <span class="chip chip-primary">新鲜出炉</span>
                                </div>
                                <div class="order-tabs">
                                    <button v-for="cat in categories" :key="cat.id"
                                            :class="['tab', { active: selectedCategory === cat.id }]"
                                            @click="selectedCategory = cat.id">
                                        {{ cat.icon }} {{ cat.name }}
                                    </button>
                                </div>
                                <div class="order-grid">
                                    <div v-for="item in filteredItems" :key="item.id"
                                         :class="['order-card-item', { recommend: item.is_recommend }]"
                                         ref="orderItemCards"
                                         @click="addToCart(item)">
                                        <div class="oci-badge" v-if="item.is_recommend">⭐</div>
                                        <div class="oci-info">
                                            <strong>{{ item.name }}</strong>
                                            <span class="oci-price">¥{{ item.price }}</span>
                                            <span class="oci-desc">{{ item.description }}</span>
                                        </div>
                                        <button class="oci-add">+</button>
                                    </div>
                                    <div v-if="filteredItems.length === 0" class="empty" @dblclick="handleEmptyDblClick">
                                        <p>这个分类还没有菜品哦~</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="order-cart-col" ref="cartPanel">
                            <div class="card glass cart-card">
                                <div class="card-head"><h3>🛒 点餐单</h3></div>

                                <div class="cart-field">
                                    <label>👤 点餐人</label>
                                    <div class="opt-group">
                                        <button v-for="m in familyMembers" :key="m.name"
                                                :class="['opt', { active: cartMember === m.name }]"
                                                @click="cartMember = m.name; cartAvatar = m.avatar">
                                            {{ m.avatar }} {{ m.name }}
                                        </button>
                                    </div>
                                </div>
                                <div class="cart-field">
                                    <label>🍽️ 餐别</label>
                                    <div class="opt-group">
                                        <button v-for="m in mealTimes" :key="m"
                                                :class="['opt', { active: cartMealTime === m }]"
                                                @click="cartMealTime = m">{{ m }}
                                        </button>
                                    </div>
                                </div>

                                <div class="cart-items" ref="cartItems">
                                    <div v-for="(ci, idx) in cart" :key="idx" class="cart-row" ref="cartItemEls">
                                        <span>{{ ci.name }}</span>
                                        <div class="cart-row-right">
                                            <div class="qty"><button @click="changeQty(idx, -1)">−</button><span>{{ ci.quantity }}</span><button @click="changeQty(idx, 1)">+</button></div>
                                            <span class="cart-row-price">¥{{ (ci.price * ci.quantity).toFixed(2) }}</span>
                                            <button class="btn-icon" @click="removeFromCart(idx)">✕</button>
                                        </div>
                                    </div>
                                    <div v-if="cart.length === 0" class="empty-sm"><p>还没有点菜呢~ 🥰</p></div>
                                </div>

                                <div class="cart-note">
                                    <input v-model="cartNotes" placeholder="📝 写句备注…（比如：少放盐）" />
                                </div>

                                <div class="cart-foot">
                                    <div class="cart-total"><span>合计</span><strong>¥{{ cartTotal.toFixed(2) }}</strong></div>
                                    <button class="btn btn-primary btn-block" :disabled="cart.length === 0 || !cartMember || orderSubmitting" @click="submitOrder">
                                        {{ orderSubmitting ? '⏳ 提交中…' : '🎉 幸福下单！' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ====== 历史订单 ====== -->
                <div v-show="currentView === 'history'" class="page-view" ref="historyPage">
                    <div class="card glass">
                        <div class="card-head">
                            <h3>📅 历史订单</h3>
                            <div class="card-head-right">
                                <input type="date" v-model="historyDate" @change="loadHistory" class="date-inp" />
                            </div>
                        </div>
                        <div v-if="historyOrders.length === 0" class="empty" @dblclick="handleEmptyDblClick">
                            <p>{{ historyDate }} 没有订单~</p>
                        </div>
                        <div v-else class="history-list">
                            <div v-for="o in historyOrders" :key="o.id" class="history-item" ref="historyCards">
                                <div class="hi-head">
                                    <span class="oi-avatar" :style="{ background: memberColors[o.member_name] || '#f5e6d0' }">{{ o.member_avatar }}</span>
                                    <div>
                                        <strong>{{ o.member_name }}</strong>
                                        <span class="oi-meal">{{ o.meal_time || '随便吃吃' }}</span>
                                    </div>
                                    <span class="hi-total">¥{{ o.total_amount }}</span>
                                    <span :class="['status-badge', o.status]">{{ statusMap[o.status] }}</span>
                                </div>
                                <div class="oi-items">
                                    <span v-for="(oi, idx) in o.items" :key="idx" class="oi-chip">{{ oi.item_name }}<small v-if="oi.quantity > 1">×{{ oi.quantity }}</small></span>
                                </div>
                                <div class="hi-note" v-if="o.notes">📝 {{ o.notes }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- ========== 分类模态框 ========== -->
        <div v-if="showCategoryModal" class="modal-overlay" @click.self="showCategoryModal = false">
            <div class="modal" ref="categoryModal">
                <h3>📂 新增分类</h3>
                <div class="field"><label>分类名称</label><input v-model="categoryForm.name" placeholder="如：早餐、甜品…" /></div>
                <div class="field"><label>Emoji 图标</label>
                    <div class="emoji-picker">
                        <button v-for="e in emojis" :key="e" :class="['emoji-opt', { active: categoryForm.icon === e }]" @click="categoryForm.icon = e">{{ e }}</button>
                    </div>
                </div>
                <div class="modal-acts">
                    <button class="btn btn-outline btn-sm" @click="showCategoryModal = false">取消</button>
                    <button class="btn btn-primary btn-sm" @click="saveCategory">保存</button>
                </div>
            </div>
        </div>

        <!-- ========== 菜品模态框 ========== -->
        <div v-if="showItemModal" class="modal-overlay" @click.self="showItemModal = false">
            <div class="modal" ref="itemModal">
                <h3>{{ editingItem ? '✏️ 编辑菜品' : '🥘 新增菜品' }}</h3>
                <div class="field"><label>分类</label>
                    <select v-model="itemForm.category_id">
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.icon }} {{ cat.name }}</option>
                    </select>
                </div>
                <div class="field"><label>菜名</label><input v-model="itemForm.name" placeholder="输入菜名…" /></div>
                <div class="field"><label>价格（元）</label><input type="number" step="0.5" v-model="itemForm.price" placeholder="0.00" /></div>
                <div class="field"><label>描述</label><textarea v-model="itemForm.description" placeholder="简单描述一下…" rows="2"></textarea></div>
                <div class="row">
                    <label class="cb"><input type="checkbox" v-model="itemForm.is_recommend" /> ⭐ 推荐</label>
                    <label class="cb"><input type="checkbox" v-model="itemForm.is_available" /> 上架</label>
                </div>
                <div class="modal-acts">
                    <button class="btn btn-outline btn-sm" @click="showItemModal = false">取消</button>
                    <button class="btn btn-primary btn-sm" @click="saveItem">{{ editingItem ? '保存' : '添加' }}</button>
                </div>
            </div>
        </div>

        <!-- ========== 密码解锁模态框 ========== -->
        <div v-if="showPasswordModal" class="modal-overlay" @click.self="showPasswordModal = false">
            <div class="modal" ref="passwordModal">
                <h3>🔑 解锁管理模式</h3>
                <p style="color:#999;font-size:0.9rem;margin:-12px 0 18px;">输入管理员密码解锁菜单管理</p>
                <div class="field">
                    <label>管理员密码</label>
                    <input type="password" v-model="passwordInput" @keyup.enter="unlockMenu" placeholder="请输入密码…" ref="passwordInputEl" />
                </div>
                <div class="error-msg" v-if="passwordError" style="color:#e53935;font-size:0.85rem;margin-bottom:12px;">{{ passwordError }}</div>
                <div class="modal-acts">
                    <button class="btn btn-outline btn-sm" @click="showPasswordModal = false; passwordError = ''">取消</button>
                    <button class="btn btn-primary btn-sm" :disabled="passwordLoading" @click="unlockMenu">{{ passwordLoading ? '验证中…' : '🔓 解锁' }}</button>
                </div>
            </div>
        </div>

        <!-- ========== Toast ========== -->
        <transition name="toast">
            <div v-if="toast.show" class="toast" :class="toast.type" ref="toastEl">{{ toast.message }}</div>
        </transition>

        <!-- ====== 温暖页脚 · 一直在动（置于最底） ====== -->
        <footer class="footer-warm" v-if="currentView === 'home'">
            <!-- 飘浮装饰（覆盖文字区域） -->
            <div class="footer-sparkle" style="left:8%;top:20%;animation-delay:0s">✨</div>
            <div class="footer-sparkle" style="left:22%;top:40%;animation-delay:1.6s">🥟</div>
            <div class="footer-sparkle" style="left:38%;top:25%;animation-delay:0.6s">🌟</div>
            <div class="footer-sparkle" style="left:55%;top:50%;animation-delay:2.2s">❤️</div>
            <div class="footer-sparkle" style="left:70%;top:30%;animation-delay:1s">🥟</div>
            <div class="footer-sparkle" style="left:85%;top:45%;animation-delay:2.8s">✨</div>
            <div class="footer-sparkle" style="left:95%;top:35%;animation-delay:1.8s">🌟</div>
            <!-- 装饰线 -->
            <div class="footer-divider"></div>
            <div class="footer-divider sub"></div>
            <!-- 主体（在动画图层上） -->
            <div class="footer-body">
                <div class="footer-emoji-row">
                    <span class="footer-emoji">👨‍👩‍👧‍👦</span>
                </div>
                <span class="footer-title">幸福小厨 · 用心做好每一餐</span>
                <span class="footer-sub">🥟 家的味道，就是幸福的味道 🥟</span>
            </div>
        </footer>
    </div>
    <script src="assets/js/app.js?v=2.0"></script>
</body>
</html>
