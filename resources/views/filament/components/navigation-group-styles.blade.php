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
</style>