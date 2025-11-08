<x-filament-panels::page>
    <div class="space-y-4">
        <!-- شريط الأدوات -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-semibold">إدارة المدارس على الخريطة</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">انقر على الخريطة لإضافة مدرسة جديدة</p>
                </div>
                <div class="flex gap-2">
                    <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                        عدد المدارس: <span id="schools-count">{{ $this->getSchools()->count() }}</span>
                    </span>
                </div>
            </div>

            <!-- تعليمات -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-md p-3">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm">
                        <p class="font-medium text-blue-900 dark:text-blue-200 mb-1">كيفية الاستخدام:</p>
                        <ul class="list-disc list-inside space-y-1 text-blue-800 dark:text-blue-300">
                            <li><strong>إضافة مدرسة:</strong> انقر على أي مكان داخل السعودية</li>
                            <li><strong>عرض معلومات:</strong> مرر الماوس على علامة المدرسة لعرض الاسم</li>
                            <li><strong>تعديل مدرسة:</strong> انقر على علامة المدرسة ثم اختر "تعديل"</li>
                            <li><strong>نقل مدرسة:</strong> اسحب علامة المدرسة للمكان الجديد</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- الخريطة -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden" wire:ignore>
            <div id="schools-map" style="height: 650px; width: 100%;"></div>
        </div>

        <!-- أسطورة الخريطة -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <h4 class="text-sm font-semibold mb-3">دليل الخريطة</h4>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="flex items-center gap-2">
                    <div style="width: 25px; height: 41px; position: relative;">
                        <svg viewBox="0 0 384 512" style="width: 100%; height: 100%; fill: #10b981;">
                            <path d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                        </svg>
                    </div>
                    <span class="text-sm">مدرسة</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span class="text-sm">انقر لإضافة مدرسة</span>
                </div>
            </div>
        </div>

        <!-- Modal لإضافة/تعديل مدرسة -->
        <div id="school-modal" class="hidden fixed inset-0 overflow-y-auto" style="z-index: 9999;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
                <div class="fixed inset-0 bg-black bg-opacity-75 transition-opacity" onclick="closeSchoolModal()"></div>

                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-2xl max-w-md w-full p-6 transform transition-all" style="z-index: 10000;">
                    <h3 class="text-lg font-semibold mb-4" id="modal-title">إضافة مدرسة جديدة</h3>

                    <div class="space-y-4">
                        <!-- اسم المدرسة بالعربي -->
                        <div>
                            <label class="block text-sm font-medium mb-2">اسم المدرسة (عربي) *</label>
                            <input type="text"
                                   id="school-name-ar"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                   placeholder="مثال: مدرسة الرواد"
                                   autofocus>
                        </div>

                        <!-- اسم المدرسة بالإنجليزي -->
                        <div>
                            <label class="block text-sm font-medium mb-2">اسم المدرسة (English) *</label>
                            <input type="text"
                                   id="school-name-en"
                                   class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                   placeholder="Example: Al Rowad School">
                        </div>

                        <!-- الإحداثيات (للعرض فقط) -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium mb-2">خط العرض</label>
                                <input type="text"
                                       id="school-lat"
                                       disabled
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 opacity-75">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">خط الطول</label>
                                <input type="text"
                                       id="school-lng"
                                       disabled
                                       class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 opacity-75">
                            </div>
                        </div>

                        <input type="hidden" id="editing-school-id">
                    </div>

                    <!-- الأزرار -->
                    <div class="flex gap-2 justify-end mt-6">
                        <button onclick="closeSchoolModal()"
                                class="px-4 py-2 bg-gray-300 dark:bg-gray-700 dark:text-white rounded-md hover:bg-gray-400 dark:hover:bg-gray-600 transition">
                            إلغاء
                        </button>
                        <button onclick="saveSchool()"
                                id="save-school-btn"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                            حفظ
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

        <script>
            let map;
            let schoolMarkers = {};
            let tempMarker = null;
            let schools = @json($this->getSchools());

            // حدود السعودية
            const saudiBounds = [
                [16.0, 34.0],
                [32.5, 56.0]
            ];

            // تهيئة الخريطة
            function initMap() {
                // التحقق من أن الخريطة غير موجودة بالفعل
                if (map) {
                    console.log('Map already initialized');
                    return;
                }

                map = L.map('schools-map', {
                    maxBounds: saudiBounds,
                    maxBoundsViscosity: 1.0,
                    minZoom: 5,
                    maxZoom: 13
                }).setView([24.0, 45.0], 6);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap',
                    maxZoom: 18,
                }).addTo(map);

                map.fitBounds(saudiBounds);

                displayAllSchools();

                map.on('click', (e) => {
                    if (e.latlng.lat >= 16.0 && e.latlng.lat <= 32.5 &&
                        e.latlng.lng >= 34.0 && e.latlng.lng <= 56.0) {
                        openAddSchoolModal(e.latlng.lat, e.latlng.lng);
                    }
                });
            }

            function displayAllSchools() {
                schools.forEach((school) => {
                    addSchoolMarker(school);
                });
            }

            function addSchoolMarker(school) {
                const color = '#10b981'; // أخضر للمدارس

                const icon = L.divIcon({
                    html: `
                    <svg viewBox="0 0 384 512" style="width: 30px; height: 49px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));">
                        <path fill="${color}" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                        <text x="192" y="220" text-anchor="middle" fill="white" font-size="100" font-weight="bold">🏫</text>
                    </svg>
                `,
                    className: '',
                    iconSize: [30, 49],
                    iconAnchor: [15, 49],
                    popupAnchor: [0, -49],
                    tooltipAnchor: [0, -49]
                });

                const marker = L.marker([school.lat, school.lng], {
                    icon: icon,
                    draggable: true,
                    title: school.name.ar
                }).addTo(map);

                marker.bindTooltip(school.name.ar, {
                    permanent: false,
                    direction: 'lefttop',
                    offset: [20, -20],
                    className: 'custom-tooltip'
                });

                marker.on('dragend', (e) => {
                    const position = e.target.getLatLng();
                    updateSchoolLocation(school.id, position.lat, position.lng);
                });

                const popupContent = `
                <div style="min-width: 220px;">
                    <h3 style="font-weight: bold; margin-bottom: 8px; font-size: 17px; color: #1f2937;">🏫 ${school.name.ar}</h3>
                    <p style="margin: 4px 0; font-size: 13px; color: #6b7280;">${school.name.en}</p>
                    <p style="margin: 4px 0; font-size: 12px; color: #9ca3af;">📍 ${parseFloat(school.lat).toFixed(4)}, ${parseFloat(school.lng).toFixed(4)}</p>
                    <p style="margin: 10px 0 12px 0; font-size: 13px;">
                        <span style="display: inline-block; padding: 3px 10px; border-radius: 12px; font-weight: 500; background: #dcfce7; color: #166534;">
                            📦 ${school.packages_count} باقات
                        </span>
                    </p>
                    <div style="margin-top: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <button onclick="openEditSchoolModal(${school.id})"
                                style="padding: 8px 12px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            ✏️ تعديل
                        </button>
                        <button onclick="confirmDeleteSchool(${school.id})"
                                style="padding: 8px 12px; background: #ef4444; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500;">
                            🗑️ حذف
                        </button>
                    </div>
                </div>
            `;

                marker.bindPopup(popupContent, {
                    maxWidth: 250,
                    className: 'custom-popup'
                });

                schoolMarkers[school.id] = marker;
            }

            function openAddSchoolModal(lat, lng) {
                document.getElementById('modal-title').textContent = 'إضافة مدرسة جديدة';
                document.getElementById('school-name-ar').value = '';
                document.getElementById('school-name-en').value = '';
                document.getElementById('school-lat').value = lat.toFixed(6);
                document.getElementById('school-lng').value = lng.toFixed(6);
                document.getElementById('editing-school-id').value = '';
                document.getElementById('save-school-btn').textContent = 'إضافة المدرسة';

                if (tempMarker) {
                    map.removeLayer(tempMarker);
                }

                const tempIcon = L.divIcon({
                    html: `
                    <svg viewBox="0 0 384 512" style="width: 30px; height: 49px; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.4)); animation: bounce 0.6s infinite;">
                        <path fill="#10b981" d="M172.268 501.67C26.97 291.031 0 269.413 0 192 0 85.961 85.961 0 192 0s192 85.961 192 192c0 77.413-26.97 99.031-172.268 309.67-9.535 13.774-29.93 13.773-39.464 0z"/>
                        <text x="192" y="210" text-anchor="middle" fill="white" font-size="140" font-weight="bold">+</text>
                    </svg>
                `,
                    className: '',
                    iconSize: [30, 49],
                    iconAnchor: [15, 49]
                });

                tempMarker = L.marker([lat, lng], { icon: tempIcon }).addTo(map);
                document.getElementById('school-modal').classList.remove('hidden');

                setTimeout(() => {
                    document.getElementById('school-name-ar').focus();
                }, 100);
            }

            function openEditSchoolModal(schoolId) {
                const school = schools.find(s => s.id === schoolId);
                if (!school) return;

                document.getElementById('modal-title').textContent = 'تعديل المدرسة';
                document.getElementById('school-name-ar').value = school.name.ar;
                document.getElementById('school-name-en').value = school.name.en;
                document.getElementById('school-lat').value = parseFloat(school.lat).toFixed(6);
                document.getElementById('school-lng').value = parseFloat(school.lng).toFixed(6);
                document.getElementById('editing-school-id').value = school.id;
                document.getElementById('save-school-btn').textContent = 'حفظ التعديلات';

                document.getElementById('school-modal').classList.remove('hidden');

                setTimeout(() => {
                    document.getElementById('school-name-ar').focus();
                }, 100);
            }

            function closeSchoolModal() {
                document.getElementById('school-modal').classList.add('hidden');
                if (tempMarker) {
                    map.removeLayer(tempMarker);
                    tempMarker = null;
                }
            }

            async function saveSchool() {
                const nameAr = document.getElementById('school-name-ar').value.trim();
                const nameEn = document.getElementById('school-name-en').value.trim();
                const lat = parseFloat(document.getElementById('school-lat').value);
                const lng = parseFloat(document.getElementById('school-lng').value);
                const editingId = document.getElementById('editing-school-id').value;

                if (!nameAr || !nameEn) {
                    alert('يجب إدخال اسم المدرسة بالعربي والإنجليزي');
                    return;
                }

                const data = {
                    name_ar: nameAr,
                    name_en: nameEn,
                    lat: lat,
                    lng: lng
                };

                try {
                    let result;

                    if (editingId) {
                        result = await Livewire.find('{{ $this->getId() }}').call('updateSchool', editingId, data);

                        if (result) {
                            const schoolIndex = schools.findIndex(s => s.id == editingId);
                            if (schoolIndex !== -1) {
                                schools[schoolIndex] = result;
                                if (schoolMarkers[result.id]) {
                                    map.removeLayer(schoolMarkers[result.id]);
                                }
                                addSchoolMarker(result);
                            }
                        }
                    } else {
                        result = await Livewire.find('{{ $this->getId() }}').call('createSchool', data);

                        if (result) {
                            schools.push(result);
                            addSchoolMarker(result);
                            document.getElementById('schools-count').textContent = schools.length;
                        }
                    }

                    closeSchoolModal();
                } catch (error) {
                    console.error('Error saving school:', error);
                    alert('حدث خطأ أثناء الحفظ');
                }
            }

            async function updateSchoolLocation(schoolId, lat, lng) {
                try {
                    await Livewire.find('{{ $this->getId() }}').call('updateSchoolLocation', schoolId, lat, lng);

                    const school = schools.find(s => s.id === schoolId);
                    if (school) {
                        school.lat = lat;
                        school.lng = lng;
                    }
                } catch (error) {
                    console.error('Error updating location:', error);
                }
            }

            function confirmDeleteSchool(schoolId) {
                const school = schools.find(s => s.id === schoolId);
                const schoolName = school ? school.name.ar : 'المدرسة';

                if (confirm(`هل أنت متأكد من حذف "${schoolName}"؟`)) {
                    deleteSchool(schoolId);
                }
            }

            async function deleteSchool(schoolId) {
                try {
                    const result = await Livewire.find('{{ $this->getId() }}').call('deleteSchool', schoolId);

                    if (result) {
                        schools = schools.filter(s => s.id !== schoolId);

                        if (schoolMarkers[schoolId]) {
                            map.removeLayer(schoolMarkers[schoolId]);
                            delete schoolMarkers[schoolId];
                        }

                        document.getElementById('schools-count').textContent = schools.length;
                    }
                } catch (error) {
                    console.error('Error deleting school:', error);
                }
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeSchoolModal();
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                initMap();
            });

            // ✅ إعادة تهيئة الخريطة بعد Livewire updates
            Livewire.hook('morph.updated', ({ el, component }) => {
                const mapElement = document.getElementById('schools-map');
                if (mapElement && !mapElement._leaflet_id) {
                    console.log('Re-initializing schools map after Livewire update...');
                    setTimeout(() => {
                        initMap();
                    }, 100);
                } else if (map) {
                    setTimeout(() => {
                        map.invalidateSize();
                    }, 100);
                }
            });
        </script>

        <style>
            @keyframes bounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-10px); }
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

            .custom-popup .leaflet-popup-content-wrapper {
                border-radius: 10px !important;
                padding: 8px !important;
            }

            .custom-popup .leaflet-popup-content {
                margin: 8px !important;
            }

            .dark .custom-popup .leaflet-popup-content-wrapper {
                background: #1f2937 !important;
                color: white !important;
            }

            .dark .custom-popup .leaflet-popup-tip {
                background: #1f2937 !important;
            }
        </style>
    @endpush
</x-filament-panels::page>
