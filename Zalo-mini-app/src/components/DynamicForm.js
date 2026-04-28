import { getSafeLocation } from '../utils/zalo.js';
import { submitReport } from '../api/index.js';

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
                input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';
                input.id = field.id;
                input.name = field.id;
                if (field.required) input.required = true;
                input.className = 'px-4 py-2 border border-gray-300 rounded-lg text-sm';
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
            await submitReport(payload);
            alert('Gửi phản ánh thành công!');
            form.reset();
        } catch (error) {
            alert('Lỗi: ' + error.message);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span>Gửi Phản Ánh</span>';
        }
    });
    
    formContainer.appendChild(form);
    return formContainer;
}
