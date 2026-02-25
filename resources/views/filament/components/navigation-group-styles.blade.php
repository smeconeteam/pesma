<style>
    /* Jarak antar menu grup lebih rapat */
    li.fi-sidebar-group {
        margin-bottom: 0.25rem !important;
    }
    
    /* Jarak antar item dalam grup */
    li.fi-sidebar-group > ul {
        gap: 0.25rem !important;
        padding-top: 0.25rem !important;
        padding-bottom: 0.25rem !important;
        transition: all 0.2s ease-in-out;
    }
    
    /* Jarak sub-menu item */
    li.fi-sidebar-group > ul > li {
        margin-bottom: 0 !important;
    }
    
    /* Jarak untuk semua sidebar items */
    aside.fi-sidebar nav > ul {
        gap: 0.25rem !important;
    }

    /* Pindahkan tombol filter full ke kiri, luruskan dengan checkbox */
    .fi-ta-header-toolbar {
        padding-left: 0 !important;
    }

    .fi-ta-header-toolbar > div.ms-auto {
        margin-left: 0 !important;
        flex: 1 !important;
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
    }

    .fi-ta-search-field {
        margin-left: auto !important; /* Dorong kolom pencarian ke ujung kanan */
    }

    /* Pastikan tombol filter berada paling kiri di grup ini */
    .fi-ta-header-toolbar > div.ms-auto > *:not(.fi-ta-search-field) {
        order: -1 !important;
    }

    /* GLOBAL CLOUD UPLOAD DESIGN (PREMIUM POLISHED) */
    
    /* 1. Reset & Global Polish for Filament FileUpload */
    .fi-fo-file-upload .filepond--root,
    .fi-fo-file-upload .filepond--panel-root,
    .fi-fo-file-upload .filepond--hopper,
    .fi-fo-file-upload .filepond--drop-label,
    .cloud-upload {
        border: none !important;
        box-shadow: none !important;
        outline: none !important;
        margin-bottom: 0 !important;
        border-radius: 16px !important;
    }

    .fi-fo-file-upload .filepond--root {
        overflow: hidden !important;
    }

    .fi-fo-file-upload .filepond--panel-root,
    .cloud-upload {
        background-color: rgba(255, 255, 255, 0.04) !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
        border: none !important; /* Removed dashed border */
        position: relative;
    }

    .fi-fo-file-upload:hover .filepond--panel-root,
    .cloud-upload:hover {
        border-color: #3b82f6 !important;
        background-color: rgba(59, 130, 246, 0.05) !important;
        box-shadow: 0 0 25px rgba(59, 130, 246, 0.08) !important;
    }

    /* 2. Sizing & Centering */
    .fi-fo-file-upload .filepond--drop-label,
    .cloud-upload-container {
        min-height: 12rem !important;
        cursor: pointer !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 2rem !important;
    }

    /* 3. The Cloud Icon */
    .fi-fo-file-upload .filepond--drop-label label::before,
    .cloud-icon {
        content: "";
        display: block;
        width: 64px;
        height: 64px;
        background-color: rgba(59, 130, 246, 0.1); 
        border: 1px solid rgba(59, 130, 246, 0.15);
        border-radius: 50%;
        margin: 0 auto 1rem auto;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.8' stroke='%233b82f6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z' /%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: center;
        background-size: 30px;
        transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .fi-fo-file-upload:hover .filepond--drop-label label::before,
    .cloud-upload:hover .cloud-icon {
        transform: scale(1.1) translateY(-3px);
        background-color: #3b82f6; 
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.8' stroke='white'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z' /%3E%3C/svg%3E");
        box-shadow: 0 8px 15px rgba(59, 130, 246, 0.3);
    }

    /* 4. Text & Labels */
    .fi-fo-file-upload .filepond--drop-label label,
    .cloud-upload-text {
        font-size: 0.875rem !important;
        color: rgba(255, 255, 255, 0.6) !important;
        text-align: center !important;
        line-height: 1.5 !important;
    }

    .fi-fo-file-upload .filepond--drop-label label [data-filepond-item-state] {
        color: #3b82f6 !important;
        font-weight: 600 !important;
    }

    /* Hide redundant elements */
    .filepond--drop-label svg { display: none !important; }
    .filepond--panel-bottom, .filepond--panel-top { display: none !important; }
</style>
</style>