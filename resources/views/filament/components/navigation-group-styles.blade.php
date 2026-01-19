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
    
    /* Ukuran teks menu grup */
    li.fi-sidebar-group > button {
        font-size: 1rem !important; /* 15px */
        font-weight: 600 !important;
        padding-top: 0.625rem !important; /* 10px */
        padding-bottom: 0.625rem !important; /* 10px */
    }
    
    /* Perbesar icon menu grup */
    li.fi-sidebar-group > button svg {
        width: 2rem !important; /* 22px */
        height: 2rem !important; /* 22px */
    }
</style