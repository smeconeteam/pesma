<script>
    (function() {
        'use strict';
        
        let isProcessing = false;
        let processingTimeout = null;
        
        function getGroupButtons() {
            const groups = document.querySelectorAll('li.fi-sidebar-group');
            const buttons = [];
            
            groups.forEach(function(group) {
                const button = group.querySelector('button[aria-expanded]');
                if (button) {
                    buttons.push(button);
                }
            });
            
            return buttons;
        }
        
        function hasActiveItem(groupElement) {
            const activeItem = groupElement.querySelector('a.fi-active, li.fi-active');
            return activeItem !== null;
        }
        
        function closeOtherGroups(currentButton) {
            const allButtons = getGroupButtons();
            
            allButtons.forEach(function(btn) {
                if (btn !== currentButton) {
                    const groupElement = btn.closest('li.fi-sidebar-group');
                    const isOpen = btn.getAttribute('aria-expanded') === 'true';
                    
                    // Jangan tutup grup yang memiliki item aktif
                    if (isOpen && !hasActiveItem(groupElement)) {
                        btn.click();
                    }
                }
            });
        }
        
        function setupAccordion() {
            const sidebar = document.querySelector('aside.fi-sidebar nav');
            if (!sidebar) return;
            
            if (sidebar._clickHandler) {
                sidebar.removeEventListener('click', sidebar._clickHandler, true);
            }
            
            sidebar._clickHandler = function(e) {
                const clickedButton = e.target.closest('li.fi-sidebar-group > button[aria-expanded]');
                
                if (!clickedButton) return;
                
                const groupElement = clickedButton.closest('li.fi-sidebar-group');
                const isCurrentlyOpen = clickedButton.getAttribute('aria-expanded') === 'true';
                
                // Cegah penutupan grup yang memiliki item aktif
                if (isCurrentlyOpen && hasActiveItem(groupElement)) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                
                if (processingTimeout) {
                    clearTimeout(processingTimeout);
                }
                
                if (isProcessing) return;
                
                isProcessing = true;
                
                const wasOpen = isCurrentlyOpen;
                
                processingTimeout = setTimeout(function() {
                    const isNowOpen = clickedButton.getAttribute('aria-expanded') === 'true';
                    
                    if (!wasOpen && isNowOpen) {
                        closeOtherGroups(clickedButton);
                    }
                    
                    isProcessing = false;
                }, 200);
            };
            
            sidebar.addEventListener('click', sidebar._clickHandler, true);
        }
        
        function openGroupsWithActiveItems() {
            const groups = document.querySelectorAll('li.fi-sidebar-group');
            
            groups.forEach(function(group) {
                if (hasActiveItem(group)) {
                    const button = group.querySelector('button[aria-expanded]');
                    if (button) {
                        const isOpen = button.getAttribute('aria-expanded') === 'true';
                        if (!isOpen) {
                            button.click();
                        }
                    }
                }
            });
        }
        
        function closeAllGroups() {
            const buttons = getGroupButtons();
            
            buttons.forEach(function(btn) {
                const groupElement = btn.closest('li.fi-sidebar-group');
                const isOpen = btn.getAttribute('aria-expanded') === 'true';
                
                // Jangan tutup grup yang memiliki item aktif
                if (isOpen && !hasActiveItem(groupElement)) {
                    btn.click();
                }
            });
        }
        
        function init() {
            let attempts = 0;
            const maxAttempts = 20;
            
            const checkSidebar = setInterval(function() {
                attempts++;
                const sidebar = document.querySelector('aside.fi-sidebar nav');
                
                if (sidebar) {
                    clearInterval(checkSidebar);
                    
                    setTimeout(function() {
                        closeAllGroups();
                        
                        setTimeout(function() {
                            openGroupsWithActiveItems();
                            setupAccordion();
                        }, 150);
                    }, 100);
                } else if (attempts >= maxAttempts) {
                    clearInterval(checkSidebar);
                }
            }, 100);
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
        
        if (window.Livewire) {
            Livewire.hook('message.processed', function() {
                setTimeout(function() {
                    openGroupsWithActiveItems();
                    setupAccordion();
                }, 300);
            });
        }
        
    })();
</script>