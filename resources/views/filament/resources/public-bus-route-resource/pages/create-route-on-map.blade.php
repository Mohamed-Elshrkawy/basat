<x-filament-panels::page>
    <div x-data="routeMapCreator()" x-init="init()" class="space-y-4">
        <!-- Modal لإضافة اسم المحطة - مع z-index عالي -->
        <div x-show="showNameModal"
             x-cloak
             style="z-index: 99999 !important;"
             class="fixed inset-0 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black opacity-50"
                     style="z-index: 99998 !important;"
                     @click="cancelAddStop()"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6"
                     style="z-index: 99999 !important;">
                    <h3 class="text-lg font-semibold mb-4">إضافة محطة جديدة</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">اسم المحطة (عربي) *</label>
                            <input type="text"
                                   x-model="tempStopNameAr"
                                   @keydown.enter="confirmAddStop()"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                   placeholder="مثال: محطة الراحة الأولى"
                                   autofocus>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2">اسم المحطة (English) *</label>
                            <input type="text"
                                   x-model="tempStopNameEn"
                                   @keydown.enter="confirmAddStop()"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                   placeholder="Example: First Rest Stop">
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-md p-3 text-sm">
                            <p class="text-blue-800 dark:text-blue-300">
                                📍 الموقع: <span x-text="`${tempStopLat?.toFixed(6)}, ${tempStopLng?.toFixed(6)}`"></span>
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button @click="cancelAddStop()"
                                class="flex-1 px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500 transition">
                            إلغاء
                        </button>
                        <button @click="confirmAddStop()"
                                :disabled="!tempStopNameAr || !tempStopNameEn"
                                class="flex-1 px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                            إضافة
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- شريط الأدوات -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <!-- معلومات المسار -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium mb-2">اسم المسار (عربي) *</label>
                    <input type="text" x-model="routeNameAr"
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                           placeholder="مثال: الرياض - جدة">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">اسم المسار (English) *</label>
                    <input type="text" x-model="routeNameEn"
                           class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                           placeholder="Example: Riyadh - Jeddah">
                </div>
            </div>

            <!-- أوضاع التحكم -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-md p-3 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="font-medium text-blue-900 dark:text-blue-200">وضع الخريطة:</span>
                    <div class="flex gap-2">
                        <button @click="setMode('select')"
                                :class="mode === 'select' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                                class="px-4 py-2 rounded-md font-medium transition shadow-sm">
                            🖱️ تحديد المدن
                        </button>
                        <button @click="setMode('add')"
                                :class="mode === 'add' ? 'bg-green-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                                class="px-4 py-2 rounded-md font-medium transition shadow-sm">
                            ➕ إضافة محطة مخصصة
                        </button>
                        <button @click="setMode('edit')"
                                :class="mode === 'edit' ? 'bg-orange-600 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                                class="px-4 py-2 rounded-md font-medium transition shadow-sm"
                                :disabled="stops.length === 0">
                            ✏️ تعديل المواقع
                        </button>
                    </div>
                </div>

                <div class="text-sm text-blue-800 dark:text-blue-300">
                    <template x-if="mode === 'select'">
                        <div>
                            <strong>وضع التحديد:</strong> انقر على المدن لإضافتها للمسار بالترتيب
                        </div>
                    </template>
                    <template x-if="mode === 'add'">
                        <div>
                            <strong>وضع الإضافة:</strong> انقر في أي مكان على الخريطة لإضافة محطة مخصصة (سيُطلب منك إدخال الاسم)
                        </div>
                    </template>
                    <template x-if="mode === 'edit'">
                        <div>
                            <strong>وضع التعديل:</strong> اسحب المحطات لتغيير مواقعها (سيتم إعادة حساب المسافات تلقائياً)
                        </div>
                    </template>
                </div>
            </div>

            <!-- تعليمات -->
            <div class="bg-amber-50 dark:bg-amber-900/20 rounded-md p-3 mb-4">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-amber-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm">
                        <p class="font-medium text-amber-900 dark:text-amber-200 mb-1">ملاحظات هامة:</p>
                        <ul class="list-disc list-inside space-y-1 text-amber-800 dark:text-amber-300">
                            <li>يمكنك إضافة محطات مدن أو محطات مخصصة في أي مكان</li>
                            <li>سيتم حساب المسافة والوقت الفعلي على الطريق تلقائياً</li>
                            <li>يمكنك سحب المحطات في وضع التعديل لتغيير مواقعها</li>
                            <li>الحد الأدنى: محطتين (بداية ونهاية)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- أزرار التحكم -->
            <div class="flex flex-wrap gap-2">
                <button @click="clearRoute()"
                        :disabled="stops.length === 0"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        مسح المسار
                    </span>
                </button>

                <button @click="saveRouteData()"
                        :disabled="stops.length < 2 || !routeNameAr || !routeNameEn || isCalculating"
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed ml-auto transition">
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!isCalculating">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg class="animate-spin w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="isCalculating" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span x-text="isCalculating ? 'جاري الحساب...' : 'حفظ المسار'"></span>
                    </span>
                </button>
            </div>

            <!-- معلومات المسار الحالي -->
            <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 rounded-md" x-show="stops.length >= 2" style="display: none;">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
                    <div>
                        <span class="font-semibold">المسافة الإجمالية:</span>
                        <span x-text="totalDistance.toFixed(2) + ' كم'"></span>
                    </div>
                    <div>
                        <span class="font-semibold">الوقت المتوقع:</span>
                        <span x-text="formatTime(totalTime)"></span>
                    </div>
                    <div>
                        <span class="font-semibold">عدد المحطات:</span>
                        <span x-text="stops.length"></span>
                    </div>
                    <div>
                        <span class="font-semibold">من:</span>
                        <span x-text="stops[0]?.nameAr || '-'"></span>
                    </div>
                    <div>
                        <span class="font-semibold">إلى:</span>
                        <span x-text="stops[stops.length - 1]?.nameAr || '-'"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- الخريطة -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
            <div id="route-map" style="height: 650px; width: 100%;"></div>
        </div>

        <!-- دليل الألوان -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h4 class="text-sm font-semibold mb-3">دليل الخريطة</h4>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
                <div class="flex items-center gap-2">
                    <div style="width: 25px; height: 41px;">
                        <svg viewBox="0 0 384 512" style="width: 100%; height: 100%; fill: #9ca3af;">
                            <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                        </svg>
                    </div>
                    <span>مدن متاحة</span>
                </div>
                <div class="flex items-center gap-2">
                    <div style="width: 25px; height: 41px;">
                        <svg viewBox="0 0 384 512" style="width: 100%; height: 100%; fill: #10b981;">
                            <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                        </svg>
                    </div>
                    <span>محطة البداية</span>
                </div>
                <div class="flex items-center gap-2">
                    <div style="width: 25px; height: 41px;">
                        <svg viewBox="0 0 384 512" style="width: 100%; height: 100%; fill: #ef4444;">
                            <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                        </svg>
                    </div>
                    <span>محطة النهاية</span>
                </div>
                <div class="flex items-center gap-2">
                    <div style="width: 25px; height: 41px;">
                        <svg viewBox="0 0 384 512" style="width: 100%; height: 100%; fill: #3b82f6;">
                            <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                        </svg>
                    </div>
                    <span>محطات وسطى</span>
                </div>
                <div class="flex items-center gap-2">
                    <div style="width: 25px; height: 41px;">
                        <svg viewBox="0 0 384 512" style="width: 100%; height: 100%; fill: #f59e0b;">
                            <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                        </svg>
                    </div>
                    <span>محطات مخصصة</span>
                </div>
            </div>
        </div>

        <!-- قائمة المحطات على المسار -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4" x-show="stops.length > 0" style="display: none;">
            <h3 class="text-lg font-semibold mb-3">المحطات على المسار (<span x-text="stops.length"></span>)</h3>
            <div class="space-y-2">
                <template x-for="(stop, index) in stops" :key="index">
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-900 rounded-md">
                        <div class="flex items-center gap-3 flex-1">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold flex-shrink-0"
                                  :class="{
                                      'bg-green-600 text-white': index === 0,
                                      'bg-red-600 text-white': index === stops.length - 1,
                                      'bg-blue-600 text-white': index !== 0 && index !== stops.length - 1 && stop.type === 'city',
                                      'bg-orange-600 text-white': stop.type === 'custom'
                                  }"
                                  x-text="index + 1"></span>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <div class="font-medium" x-text="stop.nameAr"></div>
                                    <span x-show="stop.type === 'custom'"
                                          class="text-xs bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 px-2 py-0.5 rounded">
                                        محطة مخصصة
                                    </span>
                                </div>
                                <div class="text-sm text-gray-500" x-text="stop.nameEn"></div>
                                <div class="flex flex-wrap gap-2 mt-1">
                                    <span class="text-xs text-gray-400" x-text="`${stop.lat.toFixed(4)}, ${stop.lng.toFixed(4)}`"></span>
                                    <span x-show="stop.distanceFromPrevious > 0" class="text-xs">
                                        <span class="font-semibold text-blue-600" x-text="`🚗 ${stop.distanceFromPrevious.toFixed(2)} كم`"></span>
                                        <span class="text-gray-400 mx-1">•</span>
                                        <span class="font-semibold text-orange-600" x-text="`⏱️ ${stop.estimatedTimeMinutes} دقيقة`"></span>
                                        <span class="text-gray-500 text-xs" x-text="`من المحطة السابقة`"></span>
                                    </span>
                                    <span x-show="stop.cumulativeTimeMinutes > 0" class="text-xs">
                                        <span class="text-gray-400">•</span>
                                        <span class="font-semibold text-purple-600" x-text="`⏰ ${formatTime(stop.cumulativeTimeMinutes)} من البداية`"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="editStopName(index)"
                                    x-show="stop.type === 'custom'"
                                    class="text-blue-600 hover:text-blue-800 p-2 transition flex-shrink-0"
                                    title="تعديل الاسم">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button @click="removeStop(index)"
                                    x-show="stops.length > 2"
                                    class="text-red-600 hover:text-red-800 p-2 transition flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

        <script>
            function routeMapCreator() {
                return {
                    map: null,
                    mode: 'select',
                    routeNameAr: '',
                    routeNameEn: '',
                    routeLine: null,
                    stops: [],
                    stopMarkers: [],
                    cityMarkers: {},
                    allCities: @json($this->getCities()),
                    totalDistance: 0,
                    totalTime: 0,
                    isCalculating: false,
                    customStopCounter: 1,

                    showNameModal: false,
                    tempStopLat: null,
                    tempStopLng: null,
                    tempStopNameAr: '',
                    tempStopNameEn: '',

                    saudiBounds: [
                        [16.0, 34.0],
                        [32.5, 56.0]
                    ],

                    init() {
                        this.map = L.map('route-map', {
                            maxBounds: this.saudiBounds,
                            maxBoundsViscosity: 1.0,
                            minZoom: 5,
                            maxZoom: 18
                        }).setView([24.0, 45.0], 6);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap',
                            maxZoom: 18,
                        }).addTo(this.map);

                        this.map.fitBounds(this.saudiBounds);
                        this.showAllCities();

                        this.map.on('click', (e) => {
                            if (this.mode === 'add') {
                                L.DomEvent.stopPropagation(e);
                                this.showAddStopDialog(e.latlng.lat, e.latlng.lng);
                            }
                        });
                    },

                    setMode(newMode) {
                        this.mode = newMode;

                        this.stopMarkers.forEach(marker => {
                            if (newMode === 'edit') {
                                marker.dragging.enable();
                            } else {
                                marker.dragging.disable();
                            }
                        });

                        if (newMode === 'add') {
                            this.map.getContainer().style.cursor = 'crosshair';
                        } else {
                            this.map.getContainer().style.cursor = '';
                        }
                    },

                    showAllCities() {
                        this.allCities.forEach(city => {
                            const icon = L.divIcon({
                                html: `
                                    <svg viewBox="0 0 384 512" style="width: 30px; height: 49px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
                                        <path fill="#9ca3af" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                                        <circle cx="192" cy="192" r="60" fill="white"/>
                                    </svg>
                                `,
                                className: '',
                                iconSize: [30, 49],
                                iconAnchor: [15, 49],
                                popupAnchor: [0, -49],
                                tooltipAnchor: [0, -49]
                            });

                            const marker = L.marker([parseFloat(city.lat), parseFloat(city.lng)], {
                                icon: icon,
                                title: city.name.ar
                            }).addTo(this.map);

                            marker.bindTooltip(city.name.ar, {
                                permanent: false,
                                direction: 'top',
                                offset: [0, -10],
                                className: 'custom-tooltip'
                            });

                            marker.on('click', (e) => {
                                if (this.mode === 'select') {
                                    L.DomEvent.stopPropagation(e);
                                    this.addCityToRoute(city);
                                }
                            });

                            const popupContent = `
                                <div style="min-width: 180px; text-align: center;">
                                    <strong style="font-size: 15px; color: #1f2937;">${city.name.ar}</strong><br>
                                    <span style="color: #6b7280; font-size: 13px;">${city.name.en}</span><br>
                                    <button onclick="window.routeCreator.addCityToRoute(${JSON.stringify(city).replace(/"/g, '&quot;')})"
                                            style="margin-top: 10px; padding: 6px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500;">
                                        ➕ إضافة للمسار
                                    </button>
                                </div>
                            `;
                            marker.bindPopup(popupContent);

                            this.cityMarkers[city.id] = marker;
                        });

                        window.routeCreator = this;
                    },

                    showAddStopDialog(lat, lng) {
                        this.tempStopLat = lat;
                        this.tempStopLng = lng;
                        this.tempStopNameAr = `محطة ${this.customStopCounter}`;
                        this.tempStopNameEn = `Stop ${this.customStopCounter}`;
                        this.showNameModal = true;

                        setTimeout(() => {
                            const input = document.querySelector('[x-model="tempStopNameAr"]');
                            if (input) input.focus();
                        }, 100);
                    },

                    async confirmAddStop() {
                        if (!this.tempStopNameAr || !this.tempStopNameEn) {
                            alert('يجب إدخال اسم المحطة بالعربي والإنجليزي');
                            return;
                        }

                        const stop = {
                            cityId: null,
                            type: 'custom',
                            lat: this.tempStopLat,
                            lng: this.tempStopLng,
                            nameAr: this.tempStopNameAr.trim(),
                            nameEn: this.tempStopNameEn.trim(),
                            order: this.stops.length + 1,
                            distanceFromPrevious: 0,
                            estimatedTimeMinutes: 0,
                            cumulativeTimeMinutes: 0
                        };

                        this.customStopCounter++;
                        this.showNameModal = false;

                        this.tempStopLat = null;
                        this.tempStopLng = null;
                        this.tempStopNameAr = '';
                        this.tempStopNameEn = '';

                        await this.addStopToRoute(stop);
                    },

                    cancelAddStop() {
                        this.showNameModal = false;
                        this.tempStopLat = null;
                        this.tempStopLng = null;
                        this.tempStopNameAr = '';
                        this.tempStopNameEn = '';
                    },

                    async addCityToRoute(city) {
                        const exists = this.stops.find(s => s.cityId === city.id && s.type === 'city');
                        if (exists) {
                            alert('هذه المدينة موجودة بالفعل في المسار');
                            return;
                        }

                        const stop = {
                            cityId: city.id,
                            type: 'city',
                            lat: parseFloat(city.lat),
                            lng: parseFloat(city.lng),
                            nameAr: city.name.ar,
                            nameEn: city.name.en,
                            order: this.stops.length + 1,
                            distanceFromPrevious: 0,
                            estimatedTimeMinutes: 0,
                            cumulativeTimeMinutes: 0
                        };

                        await this.addStopToRoute(stop);
                    },

                    async addStopToRoute(stop) {
                        if (this.stops.length > 0) {
                            const previousStop = this.stops[this.stops.length - 1];
                            this.isCalculating = true;

                            try {
                                const result = await this.calculateRouteInfo(
                                    previousStop.lat, previousStop.lng,
                                    stop.lat, stop.lng
                                );
                                stop.distanceFromPrevious = result.distance;
                                stop.estimatedTimeMinutes = result.duration;
                                stop.cumulativeTimeMinutes = previousStop.cumulativeTimeMinutes + result.duration;
                            } catch (error) {
                                console.error('Error calculating route:', error);
                                stop.distanceFromPrevious = this.calculateStraightDistance(
                                    previousStop.lat, previousStop.lng,
                                    stop.lat, stop.lng
                                );
                                stop.estimatedTimeMinutes = Math.round(stop.distanceFromPrevious * 0.8);
                                stop.cumulativeTimeMinutes = previousStop.cumulativeTimeMinutes + stop.estimatedTimeMinutes;
                            }

                            this.isCalculating = false;
                        }

                        this.stops.push(stop);
                        this.updateRouteNames();
                        this.createStopMarker(stop, this.stops.length - 1);
                        this.updateRouteLine();
                    },

                    // ✅ إنشاء marker بإحداثيات ثابتة
                    createStopMarker(stop, index) {
                        let color = '#3b82f6';

                        if (index === 0) {
                            color = '#10b981';
                        } else if (index === this.stops.length - 1) {
                            color = '#ef4444';
                        } else if (stop.type === 'custom') {
                            color = '#f59e0b';
                        }

                        const icon = L.divIcon({
                            html: `
                                <svg viewBox="0 0 384 512" style="width: 35px; height: 57px; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.4));">
                                    <path fill="${color}" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                                    <circle cx="192" cy="192" r="60" fill="white"/>
                                    <text x="192" y="210" text-anchor="middle" fill="${color}" font-size="80" font-weight="bold">${index + 1}</text>
                                </svg>
                            `,
                            className: '',
                            iconSize: [35, 57],
                            iconAnchor: [17, 57],
                            popupAnchor: [0, -57]
                        });

                        // ✅ استخدام L.latLng للثبات التام
                        const latLng = L.latLng(parseFloat(stop.lat), parseFloat(stop.lng));
                        const marker = L.marker(latLng, {
                            icon: icon,
                            draggable: this.mode === 'edit'
                        }).addTo(this.map);

                        marker.bindPopup(`
                            <div style="text-align: center;">
                                <strong>${stop.nameAr}</strong><br>
                                <span style="font-size: 12px; color: #666;">${stop.nameEn}</span>
                            </div>
                        `);

                        marker.on('dragend', async (e) => {
                            const newLatLng = e.target.getLatLng();
                            stop.lat = newLatLng.lat;
                            stop.lng = newLatLng.lng;
                            await this.recalculateRoute();
                        });

                        // ✅ حفظ reference للـ marker مع الـ stop
                        stop._marker = marker;
                        this.stopMarkers.push(marker);
                    },

                    // ✅ تحديث colors الـ markers بدون إعادة إنشاء
                    updateStopMarkersColors() {
                        this.stops.forEach((stop, index) => {
                            if (stop._marker) {
                                let color = '#3b82f6';

                                if (index === 0) {
                                    color = '#10b981';
                                } else if (index === this.stops.length - 1) {
                                    color = '#ef4444';
                                } else if (stop.type === 'custom') {
                                    color = '#f59e0b';
                                }

                                const icon = L.divIcon({
                                    html: `
                                        <svg viewBox="0 0 384 512" style="width: 35px; height: 57px; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.4));">
                                            <path fill="${color}" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                                            <circle cx="192" cy="192" r="60" fill="white"/>
                                            <text x="192" y="210" text-anchor="middle" fill="${color}" font-size="80" font-weight="bold">${index + 1}</text>
                                        </svg>
                                    `,
                                    className: '',
                                    iconSize: [35, 57],
                                    iconAnchor: [17, 57],
                                    popupAnchor: [0, -57]
                                });

                                // ✅ تحديث الـ icon فقط بدون تغيير الموقع
                                stop._marker.setIcon(icon);
                            }
                        });
                    },

                    updateRouteLine() {
                        if (this.stops.length < 2) {
                            if (this.routeLine) {
                                this.map.removeLayer(this.routeLine);
                                this.routeLine = null;
                            }
                            this.totalDistance = 0;
                            this.totalTime = 0;
                            return;
                        }

                        // ✅ إنشاء LatLng من الـ stops
                        const latlngs = this.stops.map(stop =>
                            L.latLng(parseFloat(stop.lat), parseFloat(stop.lng))
                        );

                        if (this.routeLine) {
                            // ✅ تحديث المواقع فقط
                            this.routeLine.setLatLngs(latlngs);
                        } else {
                            // ✅ إنشاء مرة واحدة
                            this.routeLine = L.polyline(latlngs, {
                                color: '#3b82f6',
                                weight: 5,
                                opacity: 0.7,
                                smoothFactor: 1
                            }).addTo(this.map);
                        }

                        this.totalDistance = this.stops.reduce((total, stop) => {
                            return total + (parseFloat(stop.distanceFromPrevious) || 0);
                        }, 0);

                        this.totalTime = this.stops.length > 0 ?
                            this.stops[this.stops.length - 1].cumulativeTimeMinutes : 0;
                    },

                    async recalculateRoute() {
                        this.isCalculating = true;

                        for (let i = 1; i < this.stops.length; i++) {
                            const previousStop = this.stops[i - 1];
                            const currentStop = this.stops[i];

                            try {
                                const result = await this.calculateRouteInfo(
                                    previousStop.lat, previousStop.lng,
                                    currentStop.lat, currentStop.lng
                                );
                                currentStop.distanceFromPrevious = result.distance;
                                currentStop.estimatedTimeMinutes = result.duration;
                                currentStop.cumulativeTimeMinutes = previousStop.cumulativeTimeMinutes + result.duration;
                            } catch (error) {
                                currentStop.distanceFromPrevious = this.calculateStraightDistance(
                                    previousStop.lat, previousStop.lng,
                                    currentStop.lat, currentStop.lng
                                );
                                currentStop.estimatedTimeMinutes = Math.round(currentStop.distanceFromPrevious * 0.8);
                                currentStop.cumulativeTimeMinutes = previousStop.cumulativeTimeMinutes + currentStop.estimatedTimeMinutes;
                            }
                        }

                        this.isCalculating = false;
                        this.updateRouteLine();
                    },

                    editStopName(index) {
                        const stop = this.stops[index];

                        const nameAr = prompt('اسم المحطة (عربي):', stop.nameAr);
                        if (nameAr === null) return;

                        const nameEn = prompt('اسم المحطة (English):', stop.nameEn);
                        if (nameEn === null) return;

                        stop.nameAr = nameAr || stop.nameAr;
                        stop.nameEn = nameEn || stop.nameEn;

                        this.updateRouteNames();

                        if (stop._marker) {
                            stop._marker.setPopupContent(`
                                <div style="text-align: center;">
                                    <strong>${stop.nameAr}</strong><br>
                                    <span style="font-size: 12px; color: #666;">${stop.nameEn}</span>
                                </div>
                            `);
                        }
                    },

                    updateRouteNames() {
                        if (this.stops.length === 1) {
                            this.routeNameAr = `${this.stops[0].nameAr}`;
                            this.routeNameEn = `${this.stops[0].nameEn}`;
                        } else if (this.stops.length >= 2) {
                            this.routeNameAr = `${this.stops[0].nameAr} - ${this.stops[this.stops.length - 1].nameAr}`;
                            this.routeNameEn = `${this.stops[0].nameEn} - ${this.stops[this.stops.length - 1].nameEn}`;
                        }
                    },

                    async calculateRouteInfo(lat1, lng1, lat2, lng2) {
                        try {
                            const url = `https://router.project-osrm.org/route/v1/driving/${lng1},${lat1};${lng2},${lat2}?overview=false`;
                            const response = await fetch(url);
                            const data = await response.json();

                            if (data.code === 'Ok' && data.routes && data.routes.length > 0) {
                                return {
                                    distance: data.routes[0].distance / 1000,
                                    duration: Math.round(data.routes[0].duration / 60)
                                };
                            } else {
                                throw new Error('No route found');
                            }
                        } catch (error) {
                            console.error('OSRM API error:', error);
                            const distance = this.calculateStraightDistance(lat1, lng1, lat2, lng2);
                            return {
                                distance: distance,
                                duration: Math.round(distance * 0.8)
                            };
                        }
                    },

                    calculateStraightDistance(lat1, lng1, lat2, lng2) {
                        const R = 6371;
                        const dLat = (lat2 - lat1) * Math.PI / 180;
                        const dLng = (lng2 - lng1) * Math.PI / 180;
                        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                            Math.sin(dLng/2) * Math.sin(dLng/2);
                        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                        return R * c;
                    },

                    formatTime(minutes) {
                        if (minutes < 60) {
                            return `${minutes} دقيقة`;
                        }
                        const hours = Math.floor(minutes / 60);
                        const mins = minutes % 60;
                        return mins > 0 ? `${hours} ساعة و ${mins} دقيقة` : `${hours} ساعة`;
                    },

                    async removeStop(index) {
                        // ✅ حذف الـ marker من الخريطة
                        const stop = this.stops[index];
                        if (stop._marker) {
                            this.map.removeLayer(stop._marker);
                        }

                        this.stopMarkers.splice(index, 1);
                        this.stops.splice(index, 1);

                        for (let i = 1; i < this.stops.length; i++) {
                            const previousStop = this.stops[i - 1];
                            const currentStop = this.stops[i];

                            this.isCalculating = true;
                            try {
                                const result = await this.calculateRouteInfo(
                                    previousStop.lat, previousStop.lng,
                                    currentStop.lat, currentStop.lng
                                );
                                currentStop.distanceFromPrevious = result.distance;
                                currentStop.estimatedTimeMinutes = result.duration;
                                currentStop.cumulativeTimeMinutes = previousStop.cumulativeTimeMinutes + result.duration;
                            } catch (error) {
                                currentStop.distanceFromPrevious = this.calculateStraightDistance(
                                    previousStop.lat, previousStop.lng,
                                    currentStop.lat, currentStop.lng
                                );
                                currentStop.estimatedTimeMinutes = Math.round(currentStop.distanceFromPrevious * 0.8);
                                currentStop.cumulativeTimeMinutes = previousStop.cumulativeTimeMinutes + currentStop.estimatedTimeMinutes;
                            }
                            this.isCalculating = false;
                        }

                        // ✅ تحديث الألوان والأرقام فقط
                        this.updateStopMarkersColors();
                        this.updateRouteNames();
                        this.updateRouteLine();
                    },

                    clearRoute() {
                        if (this.routeLine) {
                            this.map.removeLayer(this.routeLine);
                            this.routeLine = null;
                        }

                        // ✅ حذف كل الـ markers
                        this.stops.forEach(stop => {
                            if (stop._marker) {
                                this.map.removeLayer(stop._marker);
                            }
                        });

                        this.stopMarkers = [];
                        this.stops = [];
                        this.totalDistance = 0;
                        this.totalTime = 0;
                        this.routeNameAr = '';
                        this.routeNameEn = '';
                        this.customStopCounter = 1;
                    },

                    saveRouteData() {
                        if (this.stops.length < 2) {
                            alert('يجب إضافة محطتين على الأقل');
                            return;
                        }

                        if (!this.routeNameAr || !this.routeNameEn) {
                            alert('يجب إدخال اسم المسار بالعربي والإنجليزي');
                            return;
                        }

                        const data = {
                            name: {
                                ar: this.routeNameAr,
                                en: this.routeNameEn
                            },
                            start_point_name: {
                                ar: this.stops[0].nameAr,
                                en: this.stops[0].nameEn
                            },
                            end_point_name: {
                                ar: this.stops[this.stops.length - 1].nameAr,
                                en: this.stops[this.stops.length - 1].nameEn
                            },
                            start_city_id: this.stops[0].cityId,
                            end_city_id: this.stops[this.stops.length - 1].cityId,
                            range_km: this.totalDistance,
                            stops: this.stops.map((stop, index) => ({
                                city_id: stop.cityId,
                                type: stop.type,
                                name: {
                                    ar: stop.nameAr,
                                    en: stop.nameEn
                                },
                                lat: parseFloat(stop.lat),
                                lng: parseFloat(stop.lng),
                                range_meters: 2000,
                                order: index + 1,
                                distance_from_previous_km: stop.distanceFromPrevious || 0,
                                estimated_time_minutes: stop.estimatedTimeMinutes || 0,
                                cumulative_time_minutes: stop.cumulativeTimeMinutes || 0
                            }))
                        };

                        console.log('Saving route data:', data);
                        this.$wire.saveRoute(data);
                    }
                }
            }
        </script>

        <style>
            [x-cloak] { display: none !important; }

            /* ✅ تأكيد z-index للخريطة */
            #route-map {
                position: relative;
                z-index: 1;
            }

            .custom-tooltip {
                background: rgba(0, 0, 0, 0.85) !important;
                border: none !important;
                border-radius: 6px !important;
                padding: 6px 12px !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                color: white !important;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3) !important;
            }

            .custom-tooltip::before {
                border-top-color: rgba(0, 0, 0, 0.85) !important;
            }

            .leaflet-popup-content-wrapper {
                border-radius: 10px !important;
                padding: 8px !important;
            }

            .leaflet-popup-content {
                margin: 8px !important;
            }

            .dark .leaflet-popup-content-wrapper {
                background: #1f2937 !important;
                color: white !important;
            }

            .dark .leaflet-popup-tip {
                background: #1f2937 !important;
            }
        </style>
    @endpush
</x-filament-panels::page>
