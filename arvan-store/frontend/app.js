const { createApp, ref, computed, onMounted } = Vue;

const ArvanStore = {
    setup() {
        const view = ref('cloud_server');
        const balance = ref(0);
        const burnRate = ref(0);
        const hoursLeft = ref(0);
        const daysLeft = ref(0);
        const isLoggedIn = ref(false);
        const searchQuery = ref('');
        
        const chargeAmount = ref(100000);
        const customAmount = ref('');
        const loading = ref(false);
        const actionLoading = ref(null);
        
        // Sorkhab Toast & Modal State
        const toast = ref({ show: false, message: '', type: 'success' });
        const modal = ref({ show: false, title: '', message: '', action: null, targetId: null, confirmText: 'تایید' });

        const myServices = ref([]);
        const loadingServices = ref(false);

        // Sorkhab Product Catalog
        const regions = [
            { id: 'ir-thr-c1', name: 'تهران - دیتاسنتر فروغی', code: 'THR-01', icon: '🏛️', ping: '12ms' },
            { id: 'ir-tbz-c1', name: 'تبریز - دیتاسنتر شهریار', code: 'TBZ-01', icon: '🏔️', ping: '18ms' },
            { id: 'ir-isf-c1', name: 'اصفهان - دیتاسنتر زاینده‌رود', code: 'ISF-01', icon: '🌉', ping: '15ms' }
        ];

        const serverFlavors = [
            { id: 'g1-standard-1-1', name: 'استاندارد اکونومی', planCode: 'g1-1-1', cpu: '۱ هسته', ram: '۱ گیگابایت', disk: '25 GB SSD', hourly: 69, monthly: '۵۰,۰۰۰' },
            { id: 'g1-standard-2-2', name: 'عمومی و پایدار', planCode: 'g1-2-2', cpu: '۲ هسته', ram: '۲ گیگابایت', disk: '30 GB SSD', hourly: 138, monthly: '۱۰۰,۰۰۰' },
            { id: 'g1-standard-4-4', name: 'حرفه‌ای پرسرعت', planCode: 'g1-4-4', cpu: '۴ هسته', ram: '۴ گیگابایت', disk: '50 GB NVMe', hourly: 277, monthly: '۲۰۰,۰۰۰' },
            { id: 'g1-compute-8-8', name: 'محاسباتی سنگین', planCode: 'c1-8-8', cpu: '۸ هسته', ram: '۸ گیگابایت', disk: '100 GB NVMe', hourly: 554, monthly: '۴۰۰,۰۰۰' }
        ];

        const osImages = [
            { id: 'ubuntu-24.04', name: 'Ubuntu 24.04', icon: '🐧', tag: 'LTS پایدار' },
            { id: 'ubuntu-22.04', name: 'Ubuntu 22.04', icon: '🐧', tag: 'محبوب' },
            { id: 'debian-12', name: 'Debian 12', icon: '🌀', tag: 'سبک' },
            { id: 'centos-stream-9', name: 'CentOS 9', icon: '🔴', tag: 'سازمانی' },
            { id: 'windows-server-2022', name: 'Windows 2022', icon: '🪟', tag: 'Server' }
        ];

        const cdnPlans = [
            { id: 'free', name: 'پلن پایه / رایگان', desc: 'شتاب‌دهی محتوا، گواهی SSL رایگان، DDoS Protection سطح ۱', hourly: 0, monthly: 'رایگان', features: ['SSL رایگان', 'کش نامحدود', 'پشتیبانی تیکت'] },
            { id: 'growth', name: 'پلن رشد و حرفه‌ای', desc: 'کش هوشمند لبه، قوانین صفحات پیشرفته، WAF اختصاصی و بهینه‌سازی عکس', hourly: 27, monthly: '۲۰,۰۰۰', features: ['WAF اختصاصی', 'بهینه‌سازی تصاویر', 'قوانین نامحدود Page Rules'] },
            { id: 'enterprise', name: 'پلن سازمانی و امنیت پیشرفته', desc: 'حفاظت DDoS لایه‌های ۳ و ۴ و ۷، SLA آپ‌تایم ۹۹.۹۹٪، رول‌های امنیتی سفارشی', hourly: 110, monthly: '۸۰,۰۰۰', features: ['SLA ۹۹.۹۹٪', 'رول‌های امنیتی اختصاصی', 'پشتیبانی تلفنی ۲۴/۷'] }
        ];

        const storageRegions = [
            { id: 'simin', name: 'سیمین (تهران)', location: 'دیتاسنتر تهران', icon: '🏛️' },
            { id: 'shahriar', name: 'شهریار (تبریز)', location: 'دیتاسنتر تبریز', icon: '🏔️' }
        ];

        // Active Configurations
        const serverConfig = ref({
            region: 'ir-thr-c1',
            flavor_id: 'g1-standard-2-2',
            image_id: 'ubuntu-24.04',
            disk_size: 25,
            server_name: 'arvan-cloud-vm-1',
            root_password: ''
        });

        const cdnConfig = ref({
            domain: '',
            plan: 'growth',
            enable_ssl: true,
            enable_waf: true
        });

        const storageConfig = ref({
            bucket_name: '',
            region: 'simin',
            size: 50,
            access: 'private'
        });

        const showToast = (message, type = 'success') => {
            toast.value = { show: true, message, type };
            setTimeout(() => {
                toast.value.show = false;
            }, 4500);
        };

        onMounted(() => {
            const rootEl = document.getElementById('arvan-store-app');
            const dataView = rootEl ? rootEl.getAttribute('data-view') : 'cloud_server';
            view.value = dataView || 'cloud_server';

            if (window.arvanStoreData) {
                isLoggedIn.value = Boolean(window.arvanStoreData.is_logged_in);
                if (isLoggedIn.value) {
                    fetchBalance();
                    fetchUserServices();
                }
            }
        });

        const setView = (newView) => {
            view.value = newView;
            if (newView === 'my_services' && isLoggedIn.value) {
                fetchUserServices();
            }
        };

        const fetchBalance = async () => {
            try {
                const res = await fetch(`${arvanStoreData.rest_url}/wallet/balance`, {
                    headers: { 'X-WP-Nonce': arvanStoreData.nonce }
                });
                const data = await res.json();
                if (data.success) {
                    balance.value = data.balance;
                    burnRate.value = data.burn_rate;
                    hoursLeft.value = data.hours_left;
                    daysLeft.value = data.days_left;
                }
            } catch (e) {
                console.error('Failed to fetch balance', e);
            }
        };

        const fetchUserServices = async () => {
            loadingServices.value = true;
            try {
                const res = await fetch(`${arvanStoreData.rest_url}/services`, {
                    headers: { 'X-WP-Nonce': arvanStoreData.nonce }
                });
                const data = await res.json();
                if (data.success) {
                    myServices.value = data.services;
                }
            } catch (e) {
                console.error('Failed to fetch services', e);
            }
            loadingServices.value = false;
        };

        const filteredServices = computed(() => {
            if (!searchQuery.value) return myServices.value;
            const q = searchQuery.value.toLowerCase();
            return myServices.value.filter(s => {
                const name = (s.config.server_name || s.config.domain || s.config.bucket_name || '').toLowerCase();
                const ip = (s.config.assigned_ip || '').toLowerCase();
                const resId = (s.arvan_resource_id || '').toLowerCase();
                return name.includes(q) || ip.includes(q) || resId.includes(q);
            });
        });

        const chargeWallet = async () => {
            if (!isLoggedIn.value) {
                showToast('برای شارژ کیف پول، ابتدا باید وارد حساب کاربری شوید.', 'error');
                return;
            }

            let finalAmount = chargeAmount.value;
            if (customAmount.value && parseInt(customAmount.value) > 0) {
                finalAmount = parseInt(customAmount.value);
            }

            if (finalAmount <= 0) {
                showToast('لطفا مبلغ معتبری را برای شارژ وارد کنید.', 'error');
                return;
            }

            loading.value = true;

            try {
                const res = await fetch(`${arvanStoreData.rest_url}/wallet/charge`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': arvanStoreData.nonce 
                    },
                    body: JSON.stringify({ amount: finalAmount })
                });
                const data = await res.json();
                if (data.success) {
                    balance.value = data.new_balance;
                    showToast(data.message, 'success');
                    customAmount.value = '';
                    fetchBalance();
                } else {
                    showToast(data.message || 'خطا در شارژ کیف پول', 'error');
                }
            } catch (e) {
                showToast('خطای ارتباط با درگاه پرداخت سرور', 'error');
            }
            loading.value = false;
        };

        // Live Price Calculations (Sorkhab Tokenized Formula)
        const serverHourlyPrice = computed(() => {
            const f = serverFlavors.find(x => x.id === serverConfig.value.flavor_id);
            const base = f ? f.hourly : 69;
            const extraDisk = Math.max(0, serverConfig.value.disk_size - 25) * 1.6;
            return Math.round(base + extraDisk);
        });

        const cdnHourlyPrice = computed(() => {
            const p = cdnPlans.find(x => x.id === cdnConfig.value.plan);
            return p ? p.hourly : 0;
        });

        const storageHourlyPrice = computed(() => {
            return Math.round(storageConfig.value.size * 0.8);
        });

        const getCurrentHourlyPrice = () => {
            if (view.value === 'cloud_server') return serverHourlyPrice.value;
            if (view.value === 'cdn') return cdnHourlyPrice.value;
            if (view.value === 'object_storage') return storageHourlyPrice.value;
            return 0;
        };

        const submitOrder = async () => {
            if (!isLoggedIn.value) {
                showToast('برای ساخت و تحویل آنی این سرویس، لطفا وارد حساب شوید.', 'error');
                return;
            }

            loading.value = true;
            let orderConfig = {};
            let productType = view.value;

            if (productType === 'cloud_server') {
                if (!serverConfig.value.server_name) {
                    serverConfig.value.server_name = 'srv-' + Math.floor(Math.random() * 10000);
                }
                orderConfig = { ...serverConfig.value };
            } else if (productType === 'cdn') {
                if (!cdnConfig.value.domain) {
                    showToast('لطفا نام دامنه وب‌سایت خود را وارد کنید.', 'error');
                    loading.value = false;
                    return;
                }
                orderConfig = { ...cdnConfig.value };
            } else if (productType === 'object_storage') {
                if (!storageConfig.value.bucket_name) {
                    showToast('لطفا نام یکتای باکت را وارد نمایید.', 'error');
                    loading.value = false;
                    return;
                }
                orderConfig = { ...storageConfig.value };
            }

            try {
                const res = await fetch(`${arvanStoreData.rest_url}/order`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': arvanStoreData.nonce 
                    },
                    body: JSON.stringify({ 
                        product_type: productType,
                        config: orderConfig,
                        hourly_price: getCurrentHourlyPrice()
                    })
                });
                const data = await res.json();
                
                if (data.success) {
                    showToast(`سرویس با موفقیت ایجاد شد! شناسه منبع: ${data.resource_id} (IP: ${data.assigned_ip})`, 'success');
                    fetchBalance();
                    fetchUserServices();
                    view.value = 'my_services';
                } else {
                    showToast(data.message || 'خطا در ثبت و راه‌اندازی منبع', 'error');
                }
            } catch (e) {
                showToast('خطای غیرمنتظره در ارتباط با API آروان‌کلاد', 'error');
            }
            loading.value = false;
        };

        const confirmAction = (service, action) => {
            let actionName = action === 'power_off' ? 'خاموش کردن' : (action === 'power_on' ? 'روشن کردن' : (action === 'restart' ? 'راه‌اندازی مجدد (Restart)' : 'حذف دائمی (Terminate)'));
            modal.value = {
                show: true,
                title: `تایید عملیات ${actionName}`,
                message: `آیا از انجام عملیات «${actionName}» روی سرویس «${service.config.server_name || service.config.domain || service.config.bucket_name}» اطمینان دارید؟`,
                action: action,
                targetId: service.id,
                confirmText: action === 'terminate' ? 'بله، حذف کامل' : 'تایید و اجرا'
            };
        };

        const executeModalAction = async () => {
            const serviceId = modal.value.targetId;
            const action = modal.value.action;
            modal.value.show = false;
            
            actionLoading.value = serviceId + '_' + action;
            try {
                const res = await fetch(`${arvanStoreData.rest_url}/service/action`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': arvanStoreData.nonce 
                    },
                    body: JSON.stringify({ service_id: serviceId, action: action })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    fetchUserServices();
                    fetchBalance();
                } else {
                    showToast(data.message || 'خطا در اعمال دستور روی سرویس', 'error');
                }
            } catch (e) {
                showToast('خطا در برقراری ارتباط با زیرساخت ابری', 'error');
            }
            actionLoading.value = null;
        };

        return {
            view, setView, balance, burnRate, hoursLeft, daysLeft, isLoggedIn, searchQuery,
            chargeAmount, customAmount, loading, actionLoading, toast, modal, showToast,
            regions, serverFlavors, osImages, cdnPlans, storageRegions,
            serverConfig, cdnConfig, storageConfig,
            serverHourlyPrice, cdnHourlyPrice, storageHourlyPrice, getCurrentHourlyPrice,
            chargeWallet, submitOrder,
            myServices, filteredServices, loadingServices, fetchUserServices, confirmAction, executeModalAction
        };
    },
    template: `
        <div dir="rtl" class="w-full max-w-5xl mx-auto my-0 sm:my-6 pb-28 sm:pb-8 text-slate-800 font-sans text-right antialiased" style="font-family: 'Vazirmatn', -apple-system, BlinkMacSystemFont, Tahoma, sans-serif;">
            
            <!-- Sorkhab Toast -->
            <div v-if="toast.show" class="fixed bottom-24 sm:bottom-6 left-4 right-4 sm:right-auto sm:left-6 z-50 transition-all duration-300">
                <div :class="toast.type === 'error' ? 'bg-rose-900 border-rose-700 text-rose-100' : 'bg-slate-900 border-pink-500 text-white'" 
                     class="flex items-center justify-between gap-3 px-4 py-3 rounded-2xl border shadow-2xl backdrop-blur-md">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">{{ toast.type === 'error' ? '⚠️' : '🎉' }}</span>
                        <span class="text-xs sm:text-sm font-medium">{{ toast.message }}</span>
                    </div>
                    <button @click="toast.show = false" class="text-slate-400 hover:text-white font-bold px-2">&times;</button>
                </div>
            </div>

            <!-- Sorkhab Modal -->
            <div v-if="modal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl p-6 max-w-sm w-full shadow-2xl border border-pink-100 animate-scale">
                    <div class="w-10 h-10 rounded-2xl bg-pink-50 text-[#FF0066] flex items-center justify-center text-xl font-bold mb-3">
                        ⚡
                    </div>
                    <h3 class="text-base font-black text-slate-900 mb-1.5">{{ modal.title }}</h3>
                    <p class="text-xs text-slate-600 mb-5 leading-relaxed">{{ modal.message }}</p>
                    <div class="flex items-center justify-end gap-2.5">
                        <button @click="modal.show = false" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                            انصراف
                        </button>
                        <button @click="executeModalAction" 
                                :class="modal.action === 'terminate' ? 'bg-rose-600 text-white' : 'bg-[#FF0066] text-white'"
                                class="px-5 py-2 rounded-xl text-xs font-black shadow-md transition">
                            {{ modal.confirmText }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Card Shell (Seamless on Mobile, Rounded Card on Desktop) -->
            <div class="bg-white sm:rounded-3xl shadow-none sm:shadow-xl sm:shadow-pink-950/5 border-0 sm:border border-pink-100 overflow-hidden">
                
                <!-- Sorkhab Responsive Header -->
                <div class="bg-gradient-to-b from-pink-50/50 via-white to-white p-4 sm:p-6 md:p-8 border-b border-pink-100">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 sm:w-13 sm:h-13 bg-gradient-to-tr from-[#FF0066] via-[#E11D48] to-[#7928CA] rounded-2xl flex items-center justify-center text-white text-xl sm:text-2xl font-bold shadow-md shadow-[#FF0066]/25 flex-shrink-0">
                                ☁️
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h1 class="text-lg sm:text-xl md:text-2xl font-black text-slate-900 tracking-tight">
                                        محصولات ابری آروان‌کلاد
                                    </h1>
                                    <span class="text-[10px] bg-pink-50 text-[#FF0066] font-black px-2 py-0.5 rounded-full border border-pink-200">
                                        سرخاب
                                    </span>
                                </div>
                                <p class="text-[11px] sm:text-xs text-slate-500 mt-0.5">
                                    سرور ابری (ECC)، شبکه توزیع محتوا (CDN) و فضای ابری (S3)
                                </p>
                            </div>
                        </div>

                        <!-- Wallet Status Chip -->
                        <div class="w-full sm:w-auto bg-white p-2.5 px-4 rounded-2xl border border-pink-200/80 shadow-sm flex items-center justify-between sm:justify-start gap-3">
                            <div v-if="isLoggedIn">
                                <div class="text-[10px] text-slate-400 font-bold">موجودی کیف پول:</div>
                                <div class="text-base sm:text-lg font-black text-[#FF0066] tracking-tight">{{ balance.toLocaleString() }} <span class="text-xs text-slate-500 font-normal">تومان</span></div>
                            </div>
                            <div v-else>
                                <div class="text-[10px] text-slate-400 font-bold">وضعیت حساب:</div>
                                <div class="text-xs font-bold text-slate-700">مهمان (پیش‌نمایش)</div>
                            </div>

                            <button v-if="isLoggedIn" @click="setView('wallet')" class="text-xs bg-[#FF0066] hover:bg-[#E6005C] text-white transition px-3 py-1.5 rounded-xl font-bold shadow-sm">
                                + شارژ
                            </button>
                            <a v-else href="/wp-login.php" class="text-xs bg-[#FF0066] hover:bg-[#E6005C] text-white transition px-3.5 py-1.5 rounded-xl font-bold shadow-sm">
                                ورود
                            </a>
                        </div>
                    </div>

                    <!-- Scrollable Mobile Tabs / Grid Desktop Tabs -->
                    <div class="mt-4 sm:mt-6 flex overflow-x-auto no-scrollbar gap-1.5 sm:grid sm:grid-cols-5 bg-slate-100 p-1.5 rounded-2xl border border-slate-200/80">
                        <button @click="setView('cloud_server')" 
                                :class="view==='cloud_server' ? 'bg-white text-[#FF0066] shadow-sm font-black ring-1 ring-pink-200' : 'text-slate-600 hover:text-slate-900 font-medium'" 
                                class="flex-shrink-0 whitespace-nowrap py-2.5 px-3.5 sm:px-2 rounded-xl text-xs sm:text-sm transition flex items-center justify-center gap-1.5">
                            <span>☁️</span>
                            <span>سرور ابری</span>
                        </button>
                        <button @click="setView('cdn')" 
                                :class="view==='cdn' ? 'bg-white text-[#FF0066] shadow-sm font-black ring-1 ring-pink-200' : 'text-slate-600 hover:text-slate-900 font-medium'" 
                                class="flex-shrink-0 whitespace-nowrap py-2.5 px-3.5 sm:px-2 rounded-xl text-xs sm:text-sm transition flex items-center justify-center gap-1.5">
                            <span>🌐</span>
                            <span>CDN</span>
                        </button>
                        <button @click="setView('object_storage')" 
                                :class="view==='object_storage' ? 'bg-white text-[#FF0066] shadow-sm font-black ring-1 ring-pink-200' : 'text-slate-600 hover:text-slate-900 font-medium'" 
                                class="flex-shrink-0 whitespace-nowrap py-2.5 px-3.5 sm:px-2 rounded-xl text-xs sm:text-sm transition flex items-center justify-center gap-1.5">
                            <span>🗄️</span>
                            <span>فضای ابری (S3)</span>
                        </button>
                        <button @click="setView('wallet')" 
                                :class="view==='wallet' ? 'bg-white text-[#FF0066] shadow-sm font-black ring-1 ring-pink-200' : 'text-slate-600 hover:text-slate-900 font-medium'" 
                                class="flex-shrink-0 whitespace-nowrap py-2.5 px-3.5 sm:px-2 rounded-xl text-xs sm:text-sm transition flex items-center justify-center gap-1.5">
                            <span>💳</span>
                            <span>کیف پول</span>
                        </button>
                        <button @click="setView('my_services')" 
                                :class="view==='my_services' ? 'bg-gradient-to-r from-[#FF0066] to-[#7928CA] text-white shadow-md font-black' : 'text-slate-600 hover:text-slate-900 font-medium'" 
                                class="flex-shrink-0 whitespace-nowrap py-2.5 px-3.5 sm:px-2 rounded-xl text-xs sm:text-sm transition flex items-center justify-center gap-1.5">
                            <span>🎛️</span>
                            <span>سرویس‌های من ({{ myServices.length }})</span>
                        </button>
                    </div>
                </div>

                <!-- Tab Body Area -->
                <div class="p-4 sm:p-6 md:p-8 bg-slate-50/50">
                    
                    <!-- TAB 1: CLOUD SERVER (ECC) -->
                    <div v-if="view === 'cloud_server'" class="space-y-4 sm:space-y-6">
                        
                        <!-- 1. Datacenter Region -->
                        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-slate-200 shadow-sm">
                            <h3 class="text-xs sm:text-sm font-black text-slate-900 mb-3 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-pink-100 text-[#FF0066] flex items-center justify-center text-[10px]">۱</span>
                                انتخاب دیتاسنتر (Region)
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3">
                                <div v-for="r in regions" :key="r.id" 
                                     @click="serverConfig.region = r.id"
                                     :class="serverConfig.region === r.id ? 'border-[#FF0066] bg-pink-50/40 ring-2 ring-[#FF0066]/20' : 'border-slate-200 hover:border-pink-200 bg-white'"
                                     class="p-3.5 sm:p-4 rounded-xl sm:rounded-2xl border cursor-pointer transition flex items-center justify-between">
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-xl sm:text-2xl">{{ r.icon }}</span>
                                        <div>
                                            <div class="text-xs font-black text-slate-900">{{ r.name }}</div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">پینگ: <span class="text-emerald-600 font-bold font-mono">{{ r.ping }}</span></div>
                                        </div>
                                    </div>
                                    <div class="w-4 h-4 rounded-full border flex items-center justify-center" :class="serverConfig.region === r.id ? 'border-[#FF0066] bg-[#FF0066]' : 'border-slate-300'">
                                        <div v-if="serverConfig.region === r.id" class="w-1.5 h-1.5 rounded-full bg-white"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. OS Image -->
                        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-slate-200 shadow-sm">
                            <h3 class="text-xs sm:text-sm font-black text-slate-900 mb-3 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-pink-100 text-[#FF0066] flex items-center justify-center text-[10px]">۲</span>
                                سیستم‌عامل (Image)
                            </h3>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2.5">
                                <div v-for="os in osImages" :key="os.id"
                                     @click="serverConfig.image_id = os.id"
                                     :class="serverConfig.image_id === os.id ? 'border-[#FF0066] bg-pink-50/40 ring-2 ring-[#FF0066]/20' : 'border-slate-200 hover:border-pink-200 bg-white'"
                                     class="p-3 sm:p-4 rounded-xl sm:rounded-2xl border cursor-pointer transition text-center">
                                    <div class="text-2xl sm:text-3xl mb-1">{{ os.icon }}</div>
                                    <div class="text-xs font-black text-slate-800">{{ os.name }}</div>
                                    <div class="text-[9px] text-[#FF0066] font-bold mt-1 bg-pink-50 px-1.5 py-0.5 rounded-full inline-block">{{ os.tag }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Flavor Hardware -->
                        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-slate-200 shadow-sm">
                            <h3 class="text-xs sm:text-sm font-black text-slate-900 mb-3 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-pink-100 text-[#FF0066] flex items-center justify-center text-[10px]">۳</span>
                                سخت‌افزار و پردازنده (Flavor)
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div v-for="fl in serverFlavors" :key="fl.id"
                                     @click="serverConfig.flavor_id = fl.id"
                                     :class="serverConfig.flavor_id === fl.id ? 'border-[#FF0066] bg-pink-50/40 ring-2 ring-[#FF0066]/20' : 'border-slate-200 hover:border-pink-200 bg-white'"
                                     class="p-4 rounded-xl sm:rounded-2xl border cursor-pointer transition flex items-center justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-black text-slate-900">{{ fl.name }}</span>
                                            <span class="text-[9px] bg-slate-100 text-slate-600 font-mono px-1.5 py-0.5 rounded">{{ fl.planCode }}</span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-1 flex gap-2">
                                            <span>⚡ {{ fl.cpu }}</span>
                                            <span>🧠 {{ fl.ram }}</span>
                                        </div>
                                        <div class="text-[10px] text-[#FF0066] font-bold mt-1">~{{ fl.monthly }} تومان/ماه</div>
                                    </div>
                                    <div class="text-left">
                                        <div class="text-sm sm:text-base font-black text-slate-900">{{ fl.hourly }} <span class="text-[10px] font-normal text-slate-400">تومان/ساعت</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Storage Slider & Config -->
                        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-slate-200 shadow-sm">
                            <h3 class="text-xs sm:text-sm font-black text-slate-900 mb-3 flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-pink-100 text-[#FF0066] flex items-center justify-center text-[10px]">۴</span>
                                حجم دیسک SSD NVMe و مشخصات سرور
                            </h3>
                            
                            <!-- Slider -->
                            <div class="mb-4 p-3.5 bg-slate-50 rounded-xl border border-slate-200/60">
                                <div class="flex justify-between text-xs font-bold text-slate-700 mb-1.5">
                                    <span>فضای دیسک:</span>
                                    <span class="text-[#FF0066] text-xs sm:text-sm font-black">{{ serverConfig.disk_size }} گیگابایت SSD</span>
                                </div>
                                <input type="range" min="25" max="500" step="5" v-model.number="serverConfig.disk_size" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-[#FF0066]">
                                <div class="flex justify-between text-[10px] text-slate-400 mt-1 font-mono">
                                    <span>25 GB</span>
                                    <span>500 GB</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">نام سرور (Hostname):</label>
                                    <input type="text" v-model="serverConfig.server_name" placeholder="arvan-cloud-vm-1" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-left font-mono focus:outline-none focus:border-[#FF0066]" dir="ltr">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">رمز کاربر root (اختیاری):</label>
                                    <input type="password" v-model="serverConfig.root_password" placeholder="••••••••••••" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-left font-mono focus:outline-none focus:border-[#FF0066]" dir="ltr">
                                </div>
                            </div>
                        </div>

                        <!-- Sticky Mobile & Desktop Responsive Checkout Bar -->
                        <div class="fixed sm:relative bottom-0 left-0 right-0 z-30 sm:z-auto bg-slate-950/95 sm:bg-slate-900 text-white p-3.5 sm:p-6 sm:rounded-3xl flex flex-row justify-between items-center gap-3 border-t sm:border-t-0 border-pink-500/30 backdrop-blur-md shadow-2xl">
                            <div>
                                <div class="text-[10px] sm:text-xs text-slate-400">هزینه محاسبه شده:</div>
                                <div class="text-base sm:text-2xl font-black text-pink-400 mt-0.5 flex items-baseline gap-1 sm:gap-2">
                                    <span>{{ serverHourlyPrice.toLocaleString() }}</span>
                                    <span class="text-[10px] sm:text-xs text-slate-300 font-normal">تومان/ساعت</span>
                                </div>
                            </div>
                            <button @click="submitOrder" :disabled="loading" class="px-5 sm:px-8 py-2.5 sm:py-4 bg-gradient-to-r from-[#FF0066] to-[#7928CA] hover:opacity-90 text-white font-black rounded-xl sm:rounded-2xl text-xs sm:text-sm transition shadow-lg shadow-[#FF0066]/30 flex items-center justify-center gap-1.5">
                                <span v-if="loading">در حال ساخت...</span>
                                <span v-else>🚀 ساخت آنی سرور</span>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 2: CDN -->
                    <div v-if="view === 'cdn'" class="space-y-4 sm:space-y-6">
                        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-slate-200 shadow-sm">
                            <label class="block text-xs sm:text-sm font-bold text-slate-900 mb-1.5">۱. ثبت دامنه وب‌سایت:</label>
                            <input type="text" v-model="cdnConfig.domain" placeholder="example.com" class="w-full sm:w-1/2 p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-left font-mono focus:outline-none focus:border-[#FF0066]" dir="ltr">
                        </div>

                        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-slate-200 shadow-sm">
                            <label class="block text-xs sm:text-sm font-bold text-slate-900 mb-3">۲. انتخاب پلن CDN:</label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div v-for="p in cdnPlans" :key="p.id"
                                     @click="cdnConfig.plan = p.id"
                                     :class="cdnConfig.plan === p.id ? 'border-[#FF0066] bg-pink-50/40 ring-2 ring-[#FF0066]/20' : 'border-slate-200 hover:border-pink-200 bg-white'"
                                     class="p-4 rounded-xl sm:rounded-2xl border cursor-pointer transition flex flex-col justify-between">
                                    <div>
                                        <div class="text-xs font-black text-slate-900">{{ p.name }}</div>
                                        <div class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">{{ p.desc }}</div>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center">
                                        <span class="text-xs font-bold text-[#FF0066]">{{ p.monthly }}</span>
                                        <span class="text-xs font-black text-slate-900">{{ p.hourly }} تومان/ساعت</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="fixed sm:relative bottom-0 left-0 right-0 z-30 sm:z-auto bg-slate-950/95 sm:bg-slate-900 text-white p-3.5 sm:p-6 sm:rounded-3xl flex flex-row justify-between items-center gap-3 border-t sm:border-t-0 border-pink-500/30 backdrop-blur-md shadow-2xl">
                            <div>
                                <div class="text-[10px] sm:text-xs text-slate-400">هزینه CDN:</div>
                                <div class="text-base sm:text-2xl font-black text-pink-400 mt-0.5">
                                    {{ cdnHourlyPrice.toLocaleString() }} <span class="text-[10px] sm:text-xs text-slate-300 font-normal">تومان/ساعت</span>
                                </div>
                            </div>
                            <button @click="submitOrder" :disabled="loading" class="px-5 sm:px-8 py-2.5 sm:py-4 bg-gradient-to-r from-[#FF0066] to-[#7928CA] hover:opacity-90 text-white font-black rounded-xl sm:rounded-2xl text-xs sm:text-sm transition shadow-lg shadow-[#FF0066]/30 flex items-center justify-center gap-1.5">
                                <span v-if="loading">در حال فعال‌سازی...</span>
                                <span v-else>🌐 فعال‌سازی CDN</span>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 3: OBJECT STORAGE -->
                    <div v-if="view === 'object_storage'" class="space-y-4 sm:space-y-6">
                        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-slate-200 shadow-sm">
                            <label class="block text-xs sm:text-sm font-bold text-slate-900 mb-1.5">۱. نام باکت فضای ابری (Bucket Name):</label>
                            <input type="text" v-model="storageConfig.bucket_name" placeholder="my-app-storage" class="w-full sm:w-1/2 p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm text-left font-mono focus:outline-none focus:border-[#FF0066]" dir="ltr">
                        </div>

                        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-slate-200 shadow-sm">
                            <label class="block text-xs sm:text-sm font-bold text-slate-900 mb-3">۲. تنظیمات باکت و حجم ذخیره‌سازی:</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">منطقه دیتاسنتر:</label>
                                    <select v-model="storageConfig.region" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                                        <option v-for="sr in storageRegions" :value="sr.id">{{ sr.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-700 mb-1">سطح دسترسی:</label>
                                    <select v-model="storageConfig.access" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                                        <option value="private">خصوصی (Private)</option>
                                        <option value="public-read">عمومی (Public-Read)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/60">
                                <div class="flex justify-between text-xs font-bold text-slate-700 mb-1.5">
                                    <span>حجم تخمینی:</span>
                                    <span class="text-[#FF0066] font-black">{{ storageConfig.size }} گیگابایت</span>
                                </div>
                                <input type="range" min="10" max="2000" step="10" v-model.number="storageConfig.size" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-[#FF0066]">
                            </div>
                        </div>

                        <div class="fixed sm:relative bottom-0 left-0 right-0 z-30 sm:z-auto bg-slate-950/95 sm:bg-slate-900 text-white p-3.5 sm:p-6 sm:rounded-3xl flex flex-row justify-between items-center gap-3 border-t sm:border-t-0 border-pink-500/30 backdrop-blur-md shadow-2xl">
                            <div>
                                <div class="text-[10px] sm:text-xs text-slate-400">هزینه فضای ابری:</div>
                                <div class="text-base sm:text-2xl font-black text-pink-400 mt-0.5">
                                    {{ storageHourlyPrice.toLocaleString() }} <span class="text-[10px] sm:text-xs text-slate-300 font-normal">تومان/ساعت</span>
                                </div>
                            </div>
                            <button @click="submitOrder" :disabled="loading" class="px-5 sm:px-8 py-2.5 sm:py-4 bg-gradient-to-r from-[#FF0066] to-[#7928CA] hover:opacity-90 text-white font-black rounded-xl sm:rounded-2xl text-xs sm:text-sm transition shadow-lg shadow-[#FF0066]/30 flex items-center justify-center gap-1.5">
                                <span v-if="loading">در حال ایجاد...</span>
                                <span v-else>🗄️ ایجاد باکت ابری</span>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 4: WALLET -->
                    <div v-if="view === 'wallet'" class="space-y-4 sm:space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-pink-100 shadow-sm border-r-4 border-r-[#FF0066]">
                                <div class="text-[11px] text-slate-400 font-bold mb-1">موجودی کیف پول:</div>
                                <div class="text-xl sm:text-2xl font-black text-slate-900">{{ balance.toLocaleString() }} <span class="text-xs text-slate-500 font-normal">تومان</span></div>
                            </div>
                            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm border-r-4 border-r-blue-500">
                                <div class="text-[11px] text-slate-400 font-bold mb-1">مصرف ساعتی (Burn Rate):</div>
                                <div class="text-xl sm:text-2xl font-black text-blue-600">{{ burnRate.toLocaleString() }} <span class="text-xs text-slate-500 font-normal">تومان/ساعت</span></div>
                            </div>
                            <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-sm border-r-4 border-r-purple-500">
                                <div class="text-[11px] text-slate-400 font-bold mb-1">پایداری اعتبار:</div>
                                <div class="text-xl sm:text-2xl font-black text-[#FF0066]">
                                    {{ burnRate > 0 ? (daysLeft + ' روز و ' + (hoursLeft%24) + ' س') : 'نامحدود' }}
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl border border-slate-200 shadow-sm">
                            <h3 class="text-xs sm:text-sm font-black text-slate-900 mb-3">شارژ سریع کیف پول:</h3>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mb-4">
                                <button v-for="amt in [50000, 100000, 200000, 500000]" :key="amt" 
                                        @click="chargeAmount = amt; customAmount = ''"
                                        :class="(chargeAmount === amt && !customAmount) ? 'border-[#FF0066] bg-pink-50 text-[#FF0066] font-black' : 'border-slate-200 bg-slate-50 text-slate-700'"
                                        class="p-3 rounded-xl border text-center text-xs transition">
                                    {{ (amt/1000).toLocaleString() }} هزار تومان
                                </button>
                            </div>

                            <div class="mb-4">
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">یا مبلغ دلخواه (تومان):</label>
                                <input type="number" v-model.number="customAmount" placeholder="مثلا ۳۵۰۰۰۰" class="w-full sm:w-1/2 p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            </div>

                            <button @click="chargeWallet" :disabled="loading" class="w-full sm:w-auto px-6 py-3 bg-[#FF0066] hover:bg-[#E6005C] text-white rounded-xl font-bold text-xs transition shadow-md shadow-[#FF0066]/25">
                                <span v-if="loading">در حال اتصال...</span>
                                <span v-else>💳 پرداخت و شارژ آنلاین</span>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 5: MY SERVICES -->
                    <div v-if="view === 'my_services'" class="space-y-3 sm:space-y-4">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2.5">
                            <h3 class="text-xs sm:text-sm font-black text-slate-900">سرویس‌های ابری فعال</h3>
                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                <input type="text" v-model="searchQuery" placeholder="🔍 جستجو در نام، IP یا شناسه..." class="p-2 px-3 bg-white border border-slate-200 rounded-xl text-xs w-full sm:w-52">
                                <button @click="fetchUserServices" class="text-xs text-[#FF0066] bg-pink-50 border border-pink-200 px-3 py-2 rounded-xl font-bold whitespace-nowrap">
                                    🔄 بروزرسانی
                                </button>
                            </div>
                        </div>

                        <div v-if="!isLoggedIn" class="text-center py-12 bg-white rounded-2xl border border-slate-200">
                            <div class="text-3xl mb-2">🔒</div>
                            <p class="text-xs text-slate-700 font-bold mb-3">برای مشاهده سرویس‌ها وارد حساب شوید</p>
                            <a href="/wp-login.php" class="inline-block bg-[#FF0066] text-white text-xs font-bold px-5 py-2 rounded-xl">ورود به سایت</a>
                        </div>

                        <div v-else-if="filteredServices.length === 0" class="text-center py-12 bg-white rounded-2xl border border-slate-200">
                            <div class="text-3xl mb-2">📦</div>
                            <p class="text-xs text-slate-600 font-bold">هیچ سرویس فعالی وجود ندارد.</p>
                        </div>

                        <div v-else class="space-y-3">
                            <div v-for="svc in filteredServices" :key="svc.id" class="bg-white p-4 sm:p-5 rounded-2xl border border-pink-100 shadow-sm flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl flex-shrink-0"
                                         :class="svc.product_type==='cloud_server' ? 'bg-blue-100 text-blue-700' : (svc.product_type==='cdn' ? 'bg-amber-100 text-amber-700' : 'bg-pink-100 text-[#FF0066]')">
                                        {{ svc.product_type==='cloud_server' ? '☁️' : (svc.product_type==='cdn' ? '🌐' : '🗄️') }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-xs sm:text-sm font-black text-slate-900">
                                                {{ svc.product_type==='cloud_server' ? (svc.config.server_name || 'سرور ابری') : (svc.product_type==='cdn' ? svc.config.domain : (svc.config.bucket_name || 'باکت ابری')) }}
                                            </span>
                                            <span v-if="svc.status==='active'" class="text-[9px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full">فعال</span>
                                            <span v-else-if="svc.status==='suspended'" class="text-[9px] bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded-full">معلق</span>
                                            <span v-else class="text-[9px] bg-rose-100 text-rose-800 font-bold px-2 py-0.5 rounded-full">حذف شده</span>
                                        </div>
                                        
                                        <div class="text-[11px] text-slate-500 mt-1 flex flex-wrap gap-2">
                                            <span v-if="svc.config.assigned_ip">IP: <code class="text-slate-900 font-mono font-bold bg-slate-100 px-1 rounded">{{ svc.config.assigned_ip }}</code></span>
                                            <span>هزینه: <span class="font-bold text-[#FF0066]">{{ svc.hourly_price }} ت/س</span></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div v-if="svc.status !== 'terminated'" class="flex items-center gap-1.5 w-full sm:w-auto justify-end pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                                    <button v-if="svc.product_type==='cloud_server' && svc.status==='active'" 
                                            @click="confirmAction(svc, 'restart')" 
                                            class="text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1.5 rounded-lg font-bold">
                                        🔄 ری‌استارت
                                    </button>
                                    <button v-if="svc.status==='active'" 
                                            @click="confirmAction(svc, 'power_off')" 
                                            class="text-[11px] bg-amber-50 text-amber-800 border border-amber-200 px-2.5 py-1.5 rounded-lg font-bold">
                                        ⚡ خاموش
                                    </button>
                                    <button v-else-if="svc.status==='suspended'" 
                                            @click="confirmAction(svc, 'power_on')" 
                                            class="text-[11px] bg-[#FF0066] text-white px-2.5 py-1.5 rounded-lg font-bold">
                                        ⚡ روشن
                                    </button>
                                    <button @click="confirmAction(svc, 'terminate')" 
                                            class="text-[11px] bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-1.5 rounded-lg font-bold">
                                        🗑️ حذف
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    `
};

function initArvanStore() {
    if (window.tailwind) {
        tailwind.config = { 
            theme: { 
                extend: {
                    colors: {
                        sorkhab: '#FF0066',
                        sorkhabDark: '#E6005C'
                    }
                } 
            } 
        };
    }
    const el = document.getElementById('arvan-store-app');
    if (el && !el.__vue_app__) {
        createApp(ArvanStore).mount('#arvan-store-app');
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initArvanStore);
} else {
    initArvanStore();
}
