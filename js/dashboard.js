// js/dashboard.js

/**
 * Chuyển đổi qua lại giữa các tab chức năng trên giao diện Admin
 * @param {string} tabId - ID của tab cần hiển thị (ví dụ: 'add-med-tab', 'list-med-tab')
 */
function switchTab(tabId) {
    // 1. Ẩn tất cả các nội dung tab
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('block');
        tab.classList.add('hidden');
    });

    // 2. Hiện tab được chọn
    const activeTab = document.getElementById(tabId);
    if (activeTab) {
        activeTab.classList.remove('hidden');
        activeTab.classList.add('block');
    }

    // 3. Khôi phục lại giao diện nút bấm trong Sidebar về trạng thái mặc định
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('bg-blue-50', 'text-blue-600');
        btn.classList.add('text-slate-600', 'hover:bg-slate-50', 'hover:text-slate-900');
    });

    // 4. Kích hoạt style active (nổi bật) cho nút hiện tại
    const activeBtn = document.getElementById('btn-' + tabId);
    if (activeBtn) {
        activeBtn.classList.remove('text-slate-600', 'hover:bg-slate-50', 'hover:text-slate-900');
        activeBtn.classList.add('bg-blue-50', 'text-blue-600');
    }
}