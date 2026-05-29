/**
 * 幸福小厨 🏠 家庭点单系统 v2.0
 * Vue 3 应用 + GSAP 动画 — 顶部导航版
 */
const { createApp, ref, reactive, computed, watch, nextTick, onMounted } = Vue;

const app = createApp({
    setup() {
        /* ===========================
           状态
           =========================== */
        const API_BASE = 'api/';

        const currentView = ref('home');

        // ---- 菜单管理模式锁 ----
        const menuLocked = ref(true);
        const showPasswordModal = ref(false);
        const passwordInput = ref('');
        const passwordError = ref('');
        const passwordLoading = ref(false);
        const seeding = ref(false);
        const dataLoading = ref(true);
        const orderSubmitting = ref(false);
        const apiError = ref('');

        async function seedMenu() {
            seeding.value = true;
            try {
                const res = await fetch('api/seed.php', { method: 'POST' });
                const data = await res.json();
                if (!res.ok) { showToast(data.error || '导入失败', 'error'); return; }
                showToast(data.message || '导入成功 🎉', 'success');
                await Promise.all([loadCategories(), loadItems()]);
                nextTick(() => runMenuAnim());
            } catch (e) {
                showToast('导入失败: ' + e.message, 'error');
            } finally {
                seeding.value = false;
            }
        }

        async function unlockMenu() {
            const pwd = passwordInput.value.trim();
            if (!pwd) { passwordError.value = '请输入密码'; return; }
            passwordLoading.value = true;
            passwordError.value = '';
            try {
                const res = await fetch('api/admin.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password: pwd }),
                });
                const data = await res.json();
                if (!res.ok) { passwordError.value = data.error || '密码错误'; return; }
                menuLocked.value = false;
                showPasswordModal.value = false;
                passwordInput.value = '';
                showToast('🔓 管理模式已开启', 'success');
                // 解锁动画：锁按钮弹跳
                nextTick(() => {
                    const lock = document.querySelector('.btn-lock');
                    if (lock) {
                        gsap.fromTo(lock, { scale: 0.8 }, {
                            scale: 1,
                            duration: 0.35,
                            ease: 'back.out(1.7)',
                            clearProps: 'scale'
                        });
                    }
                });
            } catch (e) {
                passwordError.value = '网络错误，请重试';
            } finally {
                passwordLoading.value = false;
            }
        }
        function lockMenu() {
            menuLocked.value = true;
            showToast('🔒 管理模式已锁定', 'success');
        }

        const navs = [
            { id: 'home',    icon: '🏠', label: '幸福首页' },
            { id: 'order',   icon: '🍳', label: '幸福点单' },
            { id: 'menu',    icon: '📋', label: '菜单管理' },
            { id: 'history', icon: '📅', label: '历史订单' },
        ];

        const viewTitles = {
            home:    '🏠 幸福首页',
            order:   '🍳 幸福点单',
            menu:    '📋 菜单管理',
            history: '📅 历史订单',
        };

        const statusMap = { pending: '待确认', confirmed: '已确认', done: '已完成' };

        const mealIcons = { '早餐': '🌅', '午餐': '☀️', '晚餐': '🌙', '加餐': '🍪' };

        const memberColors = {
            '爸爸': '#d4e9ff', '妈妈': '#ffd4e5', '爷爷': '#e8d4ff',
            '奶奶': '#ffecd4', '宝贝': '#d4ffe8',
        };

        const dailyTips = [
            { title: '🥗 均衡饮食', content: '荤素搭配，营养翻倍~' },
            { title: '💧 多喝水', content: '每天 8 杯水，健康又美丽' },
            { title: '🍳 早餐要吃好', content: '开启元气满满的一天！' },
            { title: '🧂 少盐少油', content: '清淡饮食，家人更健康' },
            { title: '🥢 一起吃饭', content: '一家人围坐，饭菜更香哦' },
            { title: '🍉 饭后水果', content: '来点水果，甜蜜收尾~' },
            { title: '😊 好心情', content: '带着笑容做的菜，最好吃！' },
        ];

        // ---- emoji 兼容映射（替换数据库中已有的老旧 emoji）
        const emojiMap = {
            '🧋': '🥤', '🥟': '🍤', '🧁': '🍪', '🥘': '🍝',
            '🥗': '🥒', '🧃': '🧉',
        };
        function safeEmoji(str) {
            if (!str) return str;
            let s = String(str);
            for (const [bad, good] of Object.entries(emojiMap)) {
                s = s.split(bad).join(good);
            }
            return s;
        }

        const currentTitle = computed(() => viewTitles[currentView.value]);

        const todayStr = computed(() => {
            const d = new Date();
            const weekdays = ['日', '一', '二', '三', '四', '五', '六'];
            return `${d.getFullYear()}年${d.getMonth() + 1}月${d.getDate()}日 周${weekdays[d.getDay()]}`;
        });

        const familyMotto = '👨‍👩‍👧‍👦 一家人就是要一起吃饭';

        /* ----- 数据 ----- */
        const categories = ref([]);
        const allItems = ref([]);
        const todayOrders = ref([]);
        const historyOrders = ref([]);
        const recommendItems = computed(() =>
            allItems.value.filter(i => i.is_recommend && i.is_available)
        );

        /* ----- 推荐左右翻页 ----- */
        const recPageSize = 7;
        const recPage = ref(0);
        const recPageTotal = computed(() => Math.ceil(recommendItems.value.length / recPageSize));
        const pagedRecItems = computed(() => {
            const start = recPage.value * recPageSize;
            return recommendItems.value.slice(start, start + recPageSize);
        });
        function prevRecPage() {
            if (recPage.value > 0) recPage.value--;
        }
        function nextRecPage() {
            if (recPage.value < recPageTotal.value - 1) recPage.value++;
        }

        /* ----- 点单 ----- */
        const selectedCategory = ref(null);
        const cart = ref([]);
        const cartMember = ref('');
        const cartAvatar = ref('👤');
        const cartMealTime = ref('午餐');
        const cartNotes = ref('');

        const familyMembers = [
            { name: '爸爸', avatar: '👨' },
            { name: '妈妈', avatar: '👩' },
            { name: '爷爷', avatar: '👴' },
            { name: '奶奶', avatar: '👵' },
            { name: '宝贝', avatar: '🧒' },
        ];
        const mealTimes = ['早餐', '午餐', '晚餐', '加餐'];
        let tipIndex = Math.floor(Math.random() * dailyTips.length);
        const randomTip = ref(dailyTips[tipIndex]);
        function refreshTip() {
            tipIndex = (tipIndex + 1) % dailyTips.length;
            randomTip.value = dailyTips[tipIndex];
        }

        const filteredItems = computed(() => {
            if (!selectedCategory.value) return allItems.value.filter(i => i.is_available);
            return allItems.value.filter(i => i.category_id == selectedCategory.value && i.is_available);
        });

        const cartTotal = computed(() =>
            cart.value.reduce((sum, i) => sum + i.price * i.quantity, 0)
        );

        /* ----- 首页统计 ----- */
        const todayRevenue = computed(() => {
            const total = todayOrders.value.reduce((s, o) => s + Number(o.total_amount), 0);
            return total.toFixed(2);
        });
        const todayMembers = computed(() => {
            const names = new Set(todayOrders.value.map(o => o.member_name));
            return names.size;
        });
        const mealOrderCounts = computed(() => {
            const counts = {};
            todayOrders.value.forEach(o => {
                const meal = o.meal_time || '其他';
                counts[meal] = (counts[meal] || 0) + 1;
            });
            return counts;
        });

        /* ----- 模态框 ----- */
        const showCategoryModal = ref(false);
        const showItemModal = ref(false);
        const editingItem = ref(null);
        const categoryForm = reactive({ name: '', icon: '🍽️' });
        const itemForm = reactive({
            category_id: '', name: '', price: 0, description: '',
            is_recommend: false, is_available: true,
        });
        const emojis = ['🍽️', '🌅', '☀️', '🌙', '🍰', '🍹', '🍜', '🥗', '🍕', '🍤', '🍲', '🥤', '🍩', '🍪', '🍚', '🥣'];

        /* ----- Toast ----- */
        const toast = reactive({ show: false, message: '', type: 'success' });
        let toastTimer = null;

        function showToast(msg, type = 'success') {
            toast.message = msg;
            toast.type = type;
            toast.show = true;
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => { toast.show = false; }, 2500);
        }

        /* ===========================
           API 调用（超时 + 鉴权中断处理）
           =========================== */
        async function api(url, method = 'GET', body = null) {
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), 15000);
            try {
                const opts = {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    signal: controller.signal,
                };
                if (body) opts.body = JSON.stringify(body);
                const res = await fetch(API_BASE + url, opts);
                clearTimeout(timer);
                const data = await res.json();
                if (!res.ok) {
                    // 鉴权过期 → 自动锁定并弹出解锁框
                    if (res.status === 401) {
                        menuLocked.value = true;
                        showPasswordModal.value = true;
                        throw new Error('🔒 登录已过期，请重新解锁');
                    }
                    throw new Error(data.error || '请求失败');
                }
                return data;
            } catch (e) {
                clearTimeout(timer);
                if (e.name === 'AbortError') throw new Error('⏱️ 请求超时，请检查网络');
                throw e;
            }
        }

        /* ----- 加载 ----- */
        async function loadCategories() {
            const data = await api('categories.php');
            categories.value = data.map(c => ({ ...c, icon: safeEmoji(c.icon) }));
            if (categories.value.length > 0 && !selectedCategory.value) {
                selectedCategory.value = categories.value[0].id;
            }
        }
        async function loadItems() {
            const items = await api('items.php');
            allItems.value = items.map(i => ({
                ...i,
                is_recommend: !!parseInt(i.is_recommend),
                is_available: !!parseInt(i.is_available),
                price: parseFloat(i.price),
                category_icon: safeEmoji(i.category_icon),
            }));
        }
        async function loadTodayOrders() {
            const date = new Date().toISOString().slice(0, 10);
            const orders = await api(`orders.php?date=${date}`);
            todayOrders.value = normalizeOrders(orders);
        }
        async function loadHistory() {
            const date = historyDate.value;
            const orders = await api(`orders.php?date=${date}`);
            historyOrders.value = normalizeOrders(orders);
        }
        function normalizeOrders(orders) {
            return orders.map(o => ({
                ...o,
                items: (o.items || []).map(oi => ({
                    ...oi,
                    quantity: parseInt(oi.quantity, 10),
                    unit_price: Number(oi.unit_price),
                })),
            }));
        }
        function formatTime(dt) {
            if (!dt) return '';
            const d = new Date(dt);
            return `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
        }
        async function loadAll() {
            dataLoading.value = true;
            try {
                await Promise.all([loadCategories(), loadItems(), loadTodayOrders()]);
                historyDate.value = new Date().toISOString().slice(0, 10);
                await loadHistory();
            } catch (e) {
                apiError.value = e.message;
                showToast('加载失败: ' + e.message, 'error');
            } finally {
                dataLoading.value = false;
            }
        }

        /* ----- 分类 ----- */
        async function saveCategory() {
            if (!categoryForm.name) return showToast('分类名称不能为空', 'error');
            try {
                await api('categories.php', 'POST', { ...categoryForm });
                showToast('分类添加成功 💕');
                showCategoryModal.value = false;
                categoryForm.name = '';
                categoryForm.icon = '🍽️';
                await loadCategories();
                runCategoryAnim();
            } catch (e) { showToast(e.message, 'error'); }
        }
        async function deleteCategory(id) {
            if (!confirm('确定要删除这个分类吗？')) return;
            await api(`categories.php?id=${id}`, 'DELETE');
            showToast('已删除');
            await loadCategories();
        }

        /* ----- 菜品 ----- */
        function openAddItemModal() {
            editingItem.value = null;
            itemForm.category_id = categories.value[0]?.id || '';
            itemForm.name = '';
            itemForm.price = 0;
            itemForm.description = '';
            itemForm.is_recommend = false;
            itemForm.is_available = true;
            showItemModal.value = true;
        }
        function openEditItemModal(item) {
            editingItem.value = item;
            itemForm.category_id = item.category_id;
            itemForm.name = item.name;
            itemForm.price = parseFloat(item.price);
            itemForm.description = item.description || '';
            itemForm.is_recommend = !!parseInt(item.is_recommend);
            itemForm.is_available = !!parseInt(item.is_available);
            showItemModal.value = true;
        }
        async function saveItem() {
            if (!itemForm.name) return showToast('菜名不能为空', 'error');
            try {
                if (editingItem.value) {
                    await api('items.php', 'PUT', { id: editingItem.value.id, ...itemForm });
                    showToast('已更新 ✅');
                } else {
                    await api('items.php', 'POST', { ...itemForm });
                    showToast('添加成功 🎉');
                }
                showItemModal.value = false;
                await loadItems();
                runItemAnim();
            } catch (e) { showToast(e.message, 'error'); }
        }
        async function deleteItem(id) {
            if (!confirm('确定删除这道菜吗？')) return;
            await api(`items.php?id=${id}`, 'DELETE');
            showToast('已删除');
            await loadItems();
        }

        /* ----- 下单 ----- */
        function addToCart(item) {
            const existing = cart.value.find(c => c.item_id === item.id);
            if (existing) {
                existing.quantity++;
            } else {
                cart.value.push({
                    item_id: item.id,
                    name: item.name,
                    price: parseFloat(item.price),
                    quantity: 1,
                });
            }
            nextTick(() => {
                const els = document.querySelectorAll('.cart-row');
                const last = els[els.length - 1];
                if (last) gsap.from(last, { x: 30, opacity: 0, duration: 0.3, ease: 'power2.out' });
            });
            showToast(`已加入：${item.name} 🥰`);
        }
        function changeQty(idx, delta) {
            const item = cart.value[idx];
            if (!item) return;
            item.quantity = Math.max(1, item.quantity + delta);
        }
        function removeFromCart(idx) {
            cart.value.splice(idx, 1);
        }
        async function submitOrder() {
            if (cart.value.length === 0 || !cartMember.value || orderSubmitting.value) return;
            orderSubmitting.value = true;
            try {
                const items = cart.value.map(c => ({
                    item_id: c.item_id,
                    item_name: c.name,
                    quantity: c.quantity,
                    price: c.price,
                }));
                const res = await api('orders.php', 'POST', {
                    member_name: cartMember.value,
                    member_avatar: cartAvatar.value,
                    items,
                    meal_time: cartMealTime.value,
                    notes: cartNotes.value,
                });
                showToast(res.message || '下单成功！开饭啦 🎉');
                celebrateOrder();
                cart.value = [];
                cartNotes.value = '';
                await loadTodayOrders();
                currentView.value = 'home';
                nextTick(() => runHomeAnim());
            } catch (e) { showToast(e.message, 'error');
            } finally { orderSubmitting.value = false; }
        }

        /* ----- 订单管理 ----- */
        async function updateOrderStatus(id, status) {
            await api('orders.php', 'PUT', { id, status });
            const msgs = { confirmed: '已确认 ✅', done: '完成啦 🎉' };
            showToast(msgs[status] || '已更新');
            await loadTodayOrders();
        }
        async function deleteOrder(id) {
            if (!confirm('确定取消这个订单吗？')) return;
            await api(`orders.php?id=${id}`, 'DELETE');
            showToast('已取消');
            await loadTodayOrders();
        }

        /* ----- 视图切换 ----- */
        function switchView(view) {
            currentView.value = view;
            nextTick(() => {
                if (view === 'home') runHomeAnim();
                else if (view === 'order') runOrderAnim();
                else if (view === 'menu') runMenuAnim();
                else if (view === 'history') runHistoryAnim();
            });
        }
        function goHome() { switchView('home'); }

        /* ===========================
           GSAP 动画 🎬
           =========================== */
        function prefersMotion() {
            return !window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
        }

        function runHomeAnim() {
            if (!prefersMotion()) return;
            const tl = gsap.timeline({ defaults: { ease: 'power3.out', duration: 0.5 } });

            const page = document.querySelector('.page-view');
            if (page) {
                gsap.set(page, { opacity: 0, y: 20 });
                tl.to(page, { opacity: 1, y: 0, duration: 0.4 });
            }

            // 英雄区
            const hero = document.querySelector('.hero');
            if (hero) {
                gsap.set(hero, { opacity: 0, scale: 0.96 });
                tl.to(hero, { opacity: 1, scale: 1, duration: 0.6, ease: 'back.out(1.4)' }, '-=0.2');
            }

            // 浮动 emoji
            const floats = document.querySelectorAll('.hero-float');
            if (floats.length) {
                gsap.set(floats, { opacity: 0, scale: 0 });
                tl.to(floats, {
                    opacity: 0.15, scale: 1, duration: 0.5, stagger: 0.08, ease: 'back.out(2)',
                }, '-=0.4');
            }

            // 英雄文字
            const heroTitle = document.querySelector('.hero-text');
            if (heroTitle) {
                gsap.set(heroTitle, { opacity: 0, y: 20 });
                tl.to(heroTitle, { opacity: 1, y: 0, duration: 0.5 }, '-=0.3');
            }

            // 指标区
            const metrics = document.querySelector('.hero-metrics');
            if (metrics) {
                gsap.set(metrics, { opacity: 0, y: 15 });
                tl.to(metrics, { opacity: 1, y: 0, duration: 0.4 }, '-=0.2');
            }

            // 按钮
            const actions = document.querySelector('.hero-actions');
            if (actions) {
                gsap.set(actions, { opacity: 0, y: 15 });
                tl.to(actions, { opacity: 1, y: 0, duration: 0.4 }, '-=0.1');
            }

            // 三餐状态条
            const strip = document.querySelector('.meal-strip');
            if (strip) {
                gsap.set(strip, { opacity: 0, y: 10 });
                tl.to(strip, { opacity: 1, y: 0, duration: 0.35 }, '-=0.1');
            }

            // 订单卡片交错
            const cards = document.querySelectorAll('.order-item');
            if (cards.length) {
                gsap.set(cards, { opacity: 0, x: -15 });
                tl.to(cards, {
                    opacity: 1, x: 0, duration: 0.4,
                    stagger: 0.08, ease: 'power2.out',
                }, '-=0.2');
            }

            // 推荐行
            const rows = document.querySelectorAll('.rec-row');
            if (rows.length) {
                gsap.set(rows, { opacity: 0, y: 10 });
                tl.to(rows, {
                    opacity: 1, y: 0, duration: 0.35,
                    stagger: 0.06, ease: 'back.out(1.2)',
                }, '-=0.2');
            }

            // 小贴士
            const tip = document.querySelector('.tip-card');
            if (tip) {
                gsap.set(tip, { opacity: 0, y: 15 });
                tl.to(tip, { opacity: 1, y: 0, duration: 0.4, ease: 'back.out(1.3)' }, '-=0.15');
            }

            createFloatingHearts();
        }

        function runOrderAnim() {
            if (!prefersMotion()) return;
            const tl = gsap.timeline({ defaults: { ease: 'power2.out', duration: 0.4 } });

            const page = document.querySelector('.page-view');
            if (page) {
                gsap.set(page, { opacity: 0, y: 20 });
                tl.to(page, { opacity: 1, y: 0 });
            }

            // 菜品卡片
            const itemCards = document.querySelectorAll('.order-card-item');
            if (itemCards.length) {
                gsap.set(itemCards, { opacity: 0, scale: 0.9 });
                tl.to(itemCards, {
                    opacity: 1, scale: 1, duration: 0.35,
                    stagger: 0.04, ease: 'back.out(1.4)',
                }, '-=0.2');
            }

            // 购物车面板
            const cartPanel = document.querySelector('.cart-card');
            if (cartPanel) {
                gsap.set(cartPanel, { opacity: 0, x: 40 });
                tl.to(cartPanel, { opacity: 1, x: 0, duration: 0.5 }, '-=0.2');
            }
        }

        function runMenuAnim() {
            if (!prefersMotion()) return;
            const tl = gsap.timeline({ defaults: { ease: 'power2.out' } });

            const page = document.querySelector('.page-view');
            if (page) {
                gsap.set(page, { opacity: 0, y: 20 });
                tl.to(page, { opacity: 1, y: 0, duration: 0.4 });
            }

            // 锁按钮
            const lockPill = document.querySelector('.btn-lock');
            if (lockPill) {
                gsap.set(lockPill, { opacity: 0, scale: 0.8 });
                tl.to(lockPill, { opacity: 1, scale: 1, duration: 0.35, ease: 'back.out(1.5)' }, '-=0.15');
            }

            // 卡片标题头
            const cardHeads = document.querySelectorAll('.menu-page .card-head, .page-view .card-head');
            if (cardHeads.length) {
                gsap.set(cardHeads, { opacity: 0, y: 8 });
                tl.to(cardHeads, {
                    opacity: 1, y: 0, duration: 0.3,
                    stagger: 0.06, ease: 'power2.out',
                }, '-=0.2');
            }

            // 分类标签
            const cats = document.querySelectorAll('.cat-tag');
            if (cats.length) {
                gsap.set(cats, { opacity: 0, scale: 0.6, y: 8 });
                tl.to(cats, {
                    opacity: 1, scale: 1, y: 0, duration: 0.35,
                    stagger: 0.05, ease: 'back.out(1.8)',
                }, '-=0.15');
            }

            // 表格行
            const rows = document.querySelectorAll('.item-table tbody tr');
            if (rows.length) {
                gsap.set(rows, { opacity: 0, x: -12 });
                tl.to(rows, {
                    opacity: 1, x: 0, duration: 0.3,
                    stagger: 0.04, ease: 'power2.out',
                }, '-=0.1');
            }
        }

        function runHistoryAnim() {
            if (!prefersMotion()) return;
            const tl = gsap.timeline({ defaults: { ease: 'power2.out' } });

            const page = document.querySelector('.page-view');
            if (page) {
                gsap.set(page, { opacity: 0, y: 20 });
                tl.to(page, { opacity: 1, y: 0, duration: 0.4 });
            }

            const cards = document.querySelectorAll('.history-item');
            if (cards.length) {
                gsap.set(cards, { opacity: 0, y: 15 });
                tl.to(cards, {
                    opacity: 1, y: 0, duration: 0.35,
                    stagger: 0.08, ease: 'power2.out',
                }, '-=0.2');
            }
        }

        function runCategoryAnim() {
            const cats = document.querySelectorAll('.cat-tag');
            if (cats.length) {
                gsap.from(cats, {
                    opacity: 0, scale: 0.8, duration: 0.3,
                    stagger: 0.05, ease: 'back.out(1.5)',
                });
            }
        }

        function runItemAnim() {
            const rows = document.querySelectorAll('.item-table tbody tr');
            if (rows.length) {
                gsap.from(rows, {
                    opacity: 0, x: -10, duration: 0.3,
                    stagger: 0.04, ease: 'power2.out',
                });
            }
        }

        /* ----- 浮动爱心装饰 ----- */
        function createFloatingHearts() {
            const container = document.querySelector('.content-scroll');
            if (!container || !prefersMotion()) return;

            document.querySelectorAll('.floating-heart').forEach(el => el.remove());

            const heartSymbols = ['❤️', '💛', '💚', '💙', '💜', '🧡', '💕', '💗'];
            for (let i = 0; i < 6; i++) {
                const heart = document.createElement('span');
                heart.className = 'floating-heart';
                heart.textContent = heartSymbols[i % heartSymbols.length];
                heart.style.cssText = `
                    position: fixed;
                    font-size: ${14 + Math.random() * 10}px;
                    pointer-events: none;
                    z-index: 0;
                    opacity: 0;
                `;
                document.body.appendChild(heart);

                const x = 80 + Math.random() * (window.innerWidth - 160);
                const y = 100 + Math.random() * 200;

                gsap.set(heart, { x, y, opacity: 0, scale: 0.5 });
                gsap.to(heart, {
                    y: y - 80 - Math.random() * 60,
                    x: x + (Math.random() - 0.5) * 40,
                    opacity: 0.25,
                    scale: 1,
                    duration: 3 + Math.random() * 2,
                    repeat: -1,
                    yoyo: true,
                    ease: 'sine.inOut',
                    delay: i * 0.5,
                });
            }
        }

        /* ============================================================
           🌤️ 隐藏彩蛋 · 温暖时光
           ============================================================ */

        /* ----- 温暖光粒子（取代原来的 emoji 爆裂）----- */
        function burstWarmth() {
            const logoEl = document.querySelector('.nav-left');
            if (!logoEl || !prefersMotion()) return;

            // 1. 导航栏泛暖光
            gsap.to('.top-nav', {
                boxShadow: '0 4px 30px rgba(232,131,58,0.2)',
                duration: 0.8,
                yoyo: true,
                repeat: 1,
                ease: 'power2.inOut',
            });

            // 2. Logo 文字微微发光 + 放大
            const logoText = document.querySelector('.nav-logo-text');
            if (logoText) {
                gsap.to(logoText, {
                    textShadow: '0 0 20px rgba(232,131,58,0.5)',
                    scale: 1.08,
                    duration: 0.6,
                    yoyo: true,
                    repeat: 1,
                    ease: 'power2.inOut',
                });
            }

            // 3. 温暖金色粒子缓缓上升
            const rect = logoEl.getBoundingClientRect();
            const cx = rect.left + rect.width / 2;
            const cy = rect.top + rect.height / 2;

            for (let i = 0; i < 16; i++) {
                const size = 4 + Math.random() * 6;
                const p = document.createElement('div');
                p.style.cssText = `
                    position: fixed;
                    left: ${cx + (Math.random() - 0.5) * 50}px;
                    top: ${cy}px;
                    width: ${size}px;
                    height: ${size}px;
                    border-radius: 50%;
                    background: radial-gradient(circle, #FFD700, #E8833A);
                    pointer-events: none;
                    z-index: 9999;
                    opacity: 0.9;
                    box-shadow: 0 0 ${size * 2}px rgba(232,131,58,0.3);
                `;
                document.body.appendChild(p);
                gsap.to(p, {
                    y: -(60 + Math.random() * 140),
                    x: (Math.random() - 0.5) * 100,
                    opacity: 0,
                    scale: 0.15,
                    duration: 1.2 + Math.random() * 0.8,
                    delay: i * 0.05,
                    ease: 'power2.out',
                    onComplete: () => p.remove(),
                });
            }
        }

        /* ============================================================
           🎉 隐藏彩蛋 · 活泼惊喜
           ============================================================ */

        /* ----- 五彩纸屑 🎊 ----- */
        function triggerConfetti(originX = window.innerWidth / 2, originY = window.innerHeight / 2) {
            if (!prefersMotion()) return;
            const colors = ['#ff6b6b', '#ffa726', '#ffee58', '#66bb6a', '#4ecdc4', '#42a5f5', '#ab47bc', '#f093fb'];
            for (let i = 0; i < 80; i++) {
                const piece = document.createElement('div');
                piece.className = 'confetti-piece';
                const color = colors[Math.floor(Math.random() * colors.length)];
                piece.style.cssText = `
                    background: ${color};
                    left: ${originX}px;
                    top: ${originY}px;
                    width: ${4 + Math.random() * 8}px;
                    height: ${4 + Math.random() * 8}px;
                    border-radius: ${Math.random() > 0.5 ? '50%' : '2px'};
                `;
                document.body.appendChild(piece);

                gsap.to(piece, {
                    x: (Math.random() - 0.5) * 600,
                    y: window.innerHeight - originY + Math.random() * 400,
                    rotation: Math.random() * 720 - 360,
                    opacity: 0,
                    duration: 1.5 + Math.random() * 2,
                    ease: 'power2.out',
                    delay: Math.random() * 0.3,
                    onComplete: () => piece.remove(),
                });
            }
        }

        /* ----- 浮动彩色泡泡 ----- */
        function createBubbles(count = 8) {
            const colors = ['rgba(255,107,107,0.12)', 'rgba(78,205,196,0.10)', 'rgba(255,167,38,0.10)',
                           'rgba(66,165,245,0.08)', 'rgba(171,71,188,0.08)'];
            const wrapper = document.querySelector('.content-scroll');
            if (!wrapper) return;
            document.querySelectorAll('.float-bubble').forEach(el => el.remove());
            for (let i = 0; i < count; i++) {
                const bubble = document.createElement('div');
                bubble.className = 'float-bubble';
                const size = 30 + Math.random() * 80;
                const color = colors[i % colors.length];
                bubble.style.cssText = `
                    width: ${size}px;
                    height: ${size}px;
                    background: ${color};
                    left: ${5 + Math.random() * 90}%;
                    top: ${60 + Math.random() * 30}%;
                `;
                wrapper.appendChild(bubble);

                gsap.set(bubble, { opacity: 0, scale: 0.3 });
                gsap.to(bubble, {
                    y: -(40 + Math.random() * 60),
                    x: (Math.random() - 0.5) * 30,
                    opacity: 0.6,
                    scale: 1,
                    duration: 4 + Math.random() * 3,
                    repeat: -1,
                    yoyo: true,
                    ease: 'sine.inOut',
                    delay: i * 0.6,
                });
            }
        }

        /* ----- Logo 彩蛋：连击 5 次触发 · 温暖星光 ----- */
        let logoClickCount = 0;
        let logoTimeout = null;
        const logoMessages = [
            '暖意渐起 ☀️',
            '温度上升 🌤️',
            '快要沸腾啦 🔥',
            '幸福满溢！ ✨',
            '',
        ];

        function handleLogoClick() {
            logoClickCount++;
            if (logoClickCount < 5) {
                showToast(logoMessages[logoClickCount - 1], 'success');
                clearTimeout(logoTimeout);
                logoTimeout = setTimeout(() => { logoClickCount = 0; }, 3000);
            }
            if (logoClickCount >= 5) {
                logoClickCount = 0;
                // 导航栏 logo 旋转
                const logo = document.querySelector('.nav-logo-text');
                if (logo) {
                    gsap.fromTo(logo, { rotation: 0 }, { rotation: 360, duration: 0.6, ease: 'power2.out' });
                }
                // 温暖光粒子
                burstWarmth();
                showToast('✨ 幸福满满，暖你一整天 ☀️', 'success');
            }
        }

        /* ----- 隐藏小猫咪 🐱 ----- */
        let catEl = null;
        let catVisible = false;
        let catClickCount = 0;
        let catTimeout = null;

        function initHiddenCat() {
            catEl = document.createElement('div');
            catEl.className = 'hidden-cat';
            catEl.textContent = '🐱';
            document.body.appendChild(catEl);
        }

        function checkCatEasterEgg() {
            catClickCount++;
            clearTimeout(catTimeout);
            catTimeout = setTimeout(() => { catClickCount = 0; }, 4000);

            if (catClickCount >= 7 && !catVisible) {
                catClickCount = 0;
                catVisible = true;
                catEl.classList.add('visible');
                showToast('😺 哇！一只小猫跳出来啦！摸摸它~', 'success');

                const followMouse = (e) => {
                    catEl.style.transform = `translate(${e.clientX - 15}px, ${e.clientY - 25}px)`;
                };
                document.addEventListener('mousemove', followMouse);

                setTimeout(() => {
                    catVisible = false;
                    catEl.classList.remove('visible');
                    document.removeEventListener('mousemove', followMouse);
                    showToast('🐱 小猫跑走啦~ 再点 7 次空白处召唤它！', 'success');
                }, 7000);

                const catchCat = () => {
                    catEl.classList.add('catch-me');
                    setTimeout(() => catEl.classList.remove('catch-me'), 500);
                    triggerConfetti(parseInt(catEl.style.transform?.match(/-?\d+/)?.[0]) || 0,
                                   parseInt(catEl.style.transform?.match(/-?\d+/g)?.[1]) || 0);
                };
                catEl.addEventListener('click', catchCat);
                setTimeout(() => catEl.removeEventListener('click', catchCat), 7000);
            }
        }

        /* ----- 偷偷跳舞模式 🕺 ----- */
        let danceMode = false;
        function toggleDanceMode() {
            danceMode = !danceMode;
            document.body.classList.toggle('dance-mode', danceMode);
            if (danceMode) {
                showToast('🕺 派对时间！所有人一起跳舞！', 'success');
                triggerConfetti();
                document.querySelectorAll('.nav-btn-icon, .member-avatar-lg, .rec-category').forEach(el => {
                    gsap.to(el, { rotation: 5, duration: 0.3, yoyo: true, repeat: -1, ease: 'sine.inOut' });
                });
            } else {
                showToast('💤 好啦，休息一下~', 'success');
                document.querySelectorAll('.nav-btn-icon, .member-avatar-lg, .rec-category').forEach(el => {
                    gsap.killTweensOf(el);
                    gsap.set(el, { rotation: 0 });
                });
            }
        }

        /* ----- 下单庆祝增强 ----- */
        function celebrateOrder() {
            triggerConfetti(window.innerWidth / 2, window.innerHeight / 3);
            setTimeout(() => {
                const btn = document.querySelector('.btn-block.btn-primary');
                if (btn) btn.classList.add('btn-celebrate');
            }, 300);
            const happyFoods = ['🍚', '🍜', '🍤', '🍲', '🍝', '🍛'];
            const food = happyFoods[Math.floor(Math.random() * happyFoods.length)];
            setTimeout(() => {
                showToast(`开饭啦 ${food} 幸福就是这么简单！`, 'success');
            }, 1200);
        }

        /* ----- 空状态彩蛋（双击触发） ----- */
        function handleEmptyDblClick() {
            const msgs = [
                '🍃 偷偷告诉你…点旁边的「去点单」试试~',
                '⭐ 其实今天适合吃顿好的！',
                '💕 有人在想你哦~',
                '🎵 啦啦啦~ 空空的也好安静呢',
                '🍳 厨房的锅已经热好啦！',
                '🤫 这是隐藏的第 6 条消息！',
            ];
            const msg = msgs[Math.floor(Math.random() * msgs.length)];
            showToast(msg, 'success');
            triggerConfetti(window.innerWidth / 2, window.innerHeight / 2);
        }

        /* ----- 全局点击彩蛋检测 ----- */
        function onBodyClick(e) {
            if (e.target === document.body || e.target.classList.contains('content-scroll') ||
                e.target.classList.contains('main-area') || e.target.closest('.empty')) {
                if (!e.target.closest('.empty')) {
                    checkCatEasterEgg();
                }
            }
        }

        /* ===========================
           初始化所有彩蛋
           =========================== */
        function initAllEasterEggs() {
            initHiddenCat();
            document.addEventListener('click', onBodyClick);
            createBubbles(6);
        }

        /* ----- 历史日期 ----- */
        const historyDate = ref(new Date().toISOString().slice(0, 10));

        /* ===========================
           Watchers
           =========================== */
        watch(selectedCategory, () => {
            nextTick(() => {
                const cards = document.querySelectorAll('.order-card-item');
                if (cards.length) {
                    gsap.from(cards, {
                        opacity: 0, scale: 0.9, duration: 0.3,
                        stagger: 0.03, ease: 'back.out(1.4)',
                    });
                }
            });
        });

        /* ===========================
           生命周期
           =========================== */
        onMounted(async () => {
            await loadAll();
            if (categories.value.length > 0 && !selectedCategory.value) {
                selectedCategory.value = categories.value[0].id;
            }
            nextTick(() => {
                runHomeAnim();
                initAllEasterEggs();
            });
        });

        /* ===========================
           返回
           =========================== */
        return {
            currentView, navs, currentTitle, todayStr, familyMotto,
            categories, allItems, todayOrders, historyOrders, recommendItems,
            statusMap, selectedCategory, filteredItems,
            cart, cartMember, cartAvatar, cartMealTime, cartNotes, cartTotal,
            familyMembers, mealTimes, mealIcons, memberColors,
            todayRevenue, todayMembers, mealOrderCounts,
            randomTip, refreshTip, formatTime,
            showCategoryModal, showItemModal, editingItem, categoryForm, itemForm, emojis,
            toast,
            switchView, goHome,
            loadHistory, historyDate,
            saveCategory, deleteCategory,
            openAddItemModal, openEditItemModal, saveItem, deleteItem,
            addToCart, changeQty, removeFromCart, submitOrder,
            updateOrderStatus, deleteOrder,
            handleLogoClick, toggleDanceMode, handleEmptyDblClick,
            menuLocked, showPasswordModal, passwordInput, passwordError, passwordLoading,
            unlockMenu, lockMenu, seeding, seedMenu, burstWarmth,
            recPage, recPageTotal, pagedRecItems, prevRecPage, nextRecPage,
            dataLoading, orderSubmitting,
        };
    },
});

app.mount('#app');
