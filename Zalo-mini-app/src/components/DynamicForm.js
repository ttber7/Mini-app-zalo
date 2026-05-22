import { getSafeLocation, getZaloUserInfo } from '../utils/zalo.js';
import { submitReport } from '../api/index.js';
import api from 'zmp-sdk';

const showToast = (message, type = 'success') => {
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-600' : 'bg-red-500';
    toast.className = `fixed top-10 left-1/2 transform -translate-x-1/2 ${bgColor} text-white px-6 py-3 rounded-full shadow-2xl z-[300] text-sm font-bold transition-all duration-300 opacity-0 translate-y-[-20px]`;
    toast.textContent = message;

    document.body.appendChild(toast);

    requestAnimationFrame(() => {
        toast.classList.remove('opacity-0', 'translate-y-[-20px]');
    });

    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-[-20px]');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

export function renderDynamicForm(data) {
    const { fields, api_submit } = data;
    
    const formContainer = document.createElement('div');
    formContainer.className = 'p-4 bg-white rounded-xl shadow-sm mb-6';
    
    const form = document.createElement('form');
    form.id = 'dynamic-form';
    form.className = 'flex flex-col space-y-4';
    
    fields.forEach(field => {
        const fieldWrapper = document.createElement('div');
        fieldWrapper.className = 'flex flex-col';
        
        const label = document.createElement('label');
        label.className = 'text-sm font-medium text-gray-700 mb-1';
        label.innerText = field.label + (field.required ? ' *' : '');
        
        let input;
        
        switch (field.type) {
            case 'location':
                input = document.createElement('div');
                input.className = 'flex items-center space-x-2';
                input.innerHTML = `
                    <input type="text" id="${field.id}" name="${field.id}" ${field.required ? 'required' : ''} 
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zalo-blue focus:border-transparent transition" 
                           placeholder="Nhập địa chỉ..." />
                    <button type="button" id="btn-get-location" class="p-2 bg-gray-100 text-zalo-blue rounded-lg hover:bg-gray-200 transition">
                        📍
                    </button>
                `;
                
                // Gắn event listener sau khi element được add vào DOM
                setTimeout(() => {
                    const btn = input.querySelector('#btn-get-location');
                    const inputField = input.querySelector(`#${field.id}`);
                    btn.addEventListener('click', async () => {
                        inputField.value = "Đang lấy vị trí...";
                        inputField.disabled = true;
                        
                        // Graceful Degradation: Nếu từ chối GPS, cho phép gõ tay
                        const loc = await getSafeLocation();
                        inputField.disabled = false;
                        if (loc) {
                            inputField.value = `${loc.latitude}, ${loc.longitude}`;
                        } else {
                            inputField.value = "";
                            inputField.placeholder = "Bị từ chối GPS. Vui lòng nhập tay địa chỉ!";
                            inputField.focus();
                        }
                    });
                }, 0);
                
                fieldWrapper.appendChild(label);
                fieldWrapper.appendChild(input);
                break;
                
            case 'image':
                input = document.createElement('div');
                input.className = 'space-y-3';
                input.innerHTML = `
                    <div class="flex items-center space-x-4">
                        <button type="button" id="btn-choose-image-${field.id}" 
                                class="px-4 py-2.5 bg-blue-50 text-blue-600 border border-blue-200 rounded-2xl font-bold text-xs hover:bg-blue-100 transition active:scale-95">
                            📸 Chọn hình ảnh
                        </button>
                        <span id="image-status-${field.id}" class="text-[11px] font-medium text-gray-400">Chưa chọn ảnh</span>
                    </div>
                    <div id="image-preview-container-${field.id}" class="hidden relative w-32 h-32 rounded-2xl overflow-hidden border border-gray-100 shadow-md group">
                        <img id="image-preview-${field.id}" class="w-full h-full object-cover" />
                        <button type="button" id="btn-remove-image-${field.id}" 
                                class="absolute top-1.5 right-1.5 w-6 h-6 bg-black/60 hover:bg-black/80 text-white rounded-full flex items-center justify-center text-xs font-bold transition active:scale-90">
                            ✕
                        </button>
                    </div>
                    <input type="hidden" id="${field.id}" name="${field.id}" ${field.required ? 'required' : ''} />
                    <input type="file" id="fallback-file-${field.id}" accept="image/*" class="hidden" />
                `;
                
                setTimeout(() => {
                    const selectBtn = input.querySelector(`#btn-choose-image-${field.id}`);
                    const statusText = input.querySelector(`#image-status-${field.id}`);
                    const previewContainer = input.querySelector(`#image-preview-container-${field.id}`);
                    const previewImg = input.querySelector(`#image-preview-${field.id}`);
                    const removeBtn = input.querySelector(`#btn-remove-image-${field.id}`);
                    const hiddenInput = input.querySelector(`#${field.id}`);
                    const fallbackFile = input.querySelector(`#fallback-file-${field.id}`);

                    const updateImage = (base64Data) => {
                        hiddenInput.value = base64Data;
                        previewImg.src = base64Data;
                        previewContainer.classList.remove('hidden');
                        statusText.classList.add('hidden');
                        selectBtn.innerHTML = '📸 Thay đổi ảnh';
                    };

                    const clearImage = () => {
                        hiddenInput.value = '';
                        previewImg.src = '';
                        previewContainer.classList.add('hidden');
                        statusText.classList.remove('hidden');
                        statusText.innerText = 'Chưa chọn ảnh';
                        selectBtn.innerHTML = '📸 Chọn hình ảnh';
                        fallbackFile.value = '';
                    };

                    removeBtn.addEventListener('click', clearImage);

                    selectBtn.addEventListener('click', () => {
                        if (typeof api !== 'undefined' && api.chooseImage) {
                            api.chooseImage({
                                count: 1,
                                sizeType: ['compressed'],
                                sourceType: ['album', 'camera'],
                                success: (res) => {
                                    if (res.tempFilePaths && res.tempFilePaths.length > 0) {
                                        const filePath = res.tempFilePaths[0];
                                        statusText.innerText = 'Đang tải ảnh...';
                                        
                                        const fs = api.getFileSystemManager();
                                        fs.readFile({
                                            filePath: filePath,
                                            encoding: 'base64',
                                            success: (readRes) => {
                                                const base64Data = `data:image/jpeg;base64,${readRes.data}`;
                                                updateImage(base64Data);
                                            },
                                            fail: (err) => {
                                                console.error('Lỗi đọc file Zalo:', err);
                                                statusText.innerText = 'Không thể đọc ảnh';
                                            }
                                        });
                                    }
                                },
                                fail: (err) => {
                                    console.warn('Lỗi chooseImage Zalo:', err);
                                }
                            });
                        } else {
                            fallbackFile.click();
                        }
                    });

                    fallbackFile.addEventListener('change', (e) => {
                        const file = e.target.files[0];
                        if (!file) return;

                        if (file.size > 10 * 1024 * 1024) {
                            showToast('Ảnh quá lớn (tối đa 10MB)', 'error');
                            fallbackFile.value = '';
                            return;
                        }

                        statusText.innerText = 'Đang xử lý...';
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            updateImage(event.target.result);
                        };
                        reader.onerror = () => {
                            statusText.innerText = 'Lỗi đọc ảnh';
                        };
                        reader.readAsDataURL(file);
                    });
                }, 0);
                
                fieldWrapper.appendChild(label);
                fieldWrapper.appendChild(input);
                break;
                
            default: // text, phone
                input = document.createElement('input');
                input.type = field.type === 'phone' ? 'tel' : 'text';
                input.id = field.id;
                input.name = field.id;
                if (field.required) input.required = true;
                input.className = 'px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-zalo-blue focus:border-transparent transition';
                input.placeholder = `Nhập ${field.label.toLowerCase()}...`;
                fieldWrapper.appendChild(label);
                fieldWrapper.appendChild(input);
                break;
        }
        
        form.appendChild(fieldWrapper);
    });
    
    // Nút Gửi
    const submitBtn = document.createElement('button');
    submitBtn.type = 'submit';
    submitBtn.className = 'w-full py-3 mt-4 bg-zalo-blue text-white font-bold rounded-lg shadow hover:bg-blue-600 transition flex justify-center items-center';
    submitBtn.innerHTML = '<span>Gửi Phản Ánh</span>';
    form.appendChild(submitBtn);
    
    // Xử lý Submit
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="animate-spin mr-2">🔄</span> Đang gửi...';
        
        try {
            // Lấy Zalo User ID thật
            const userInfo = await getZaloUserInfo();
            payload.user_id = userInfo.id || '';

            await submitReport(payload);
            showToast('Gửi phản ánh thành công!', 'success');
            form.reset();
            // Xóa ảnh preview sau khi submit thành công
            form.querySelectorAll('[id^="btn-remove-image-"]').forEach(btn => btn.click());
        } catch (error) {
            showToast('Lỗi: ' + error.message, 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span>Gửi Phản Ánh</span>';
        }
    });
    
    formContainer.appendChild(form);
    return formContainer;
}
