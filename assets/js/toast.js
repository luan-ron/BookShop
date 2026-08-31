(function (window) {
    'use strict';

    const colors = {
        success: '#10B981',
        error: '#EF4444',
        warning: '#F59E0B',
        info: '#06B6D4'
    };

    window.showToast = function (message, type = 'success') {
        const toastType = colors[type] ? type : 'info';

        if (typeof window.Toastify !== 'function') {
            return;
        }

        window.Toastify({
            text: String(message ?? ''),
            duration: 4000,
            gravity: 'top',
            position: 'right',
            close: true,
            stopOnFocus: true,
            escapeMarkup: true,
            style: {
                background: colors[toastType],
                color: '#FFFFFF',
                fontFamily: '"Times New Roman", Times, serif',
                fontSize: '16px',
                fontWeight: '400',
                letterSpacing: 'normal',
                wordSpacing: 'normal',
                textTransform: 'none',
                whiteSpace: 'normal',
                lineHeight: '1.5',
                textRendering: 'auto',
                fontFeatureSettings: 'normal',
                fontKerning: 'auto',
                direction: 'ltr',
                unicodeBidi: 'normal',
                transform: 'none',
                maxWidth: 'calc(100vw - 32px)',
                overflow: 'visible',
                borderRadius: '10px',
                boxShadow: '0 8px 24px rgba(15, 23, 42, 0.16)'
            }
        }).showToast();
    };
}(window));
