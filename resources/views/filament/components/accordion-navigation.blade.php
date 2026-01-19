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
        
        function closeOtherGroups(currentButton) {
            const allButtons = getGroupButtons();
            
            allButtons.forEach(function(btn) {
                if (btn !== currentButton) {
                    const isOpen = btn.getAttribute('aria-expanded') === 'true';
                    if (isOpen) {
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
                
                if (processingTimeout) {
                    clearTimeout(processingTimeout);
                }
                
                if (isProcessing) return;
                
                isProcessing = true;
                
                const wasOpen = clickedButton.getAttribute('aria-expanded') === 'true';
                
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
        
        function closeAllGroups() {
            const buttons = getGroupButtons();
            
            buttons.forEach(function(btn) {
                const isOpen = btn.getAttribute('aria-expanded') === 'true';
                if (isOpen) {
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
                setTimeout(setupAccordion, 300);
            });
        }
        
    })();
</script>