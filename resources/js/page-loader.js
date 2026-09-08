let loader = null;

function getLoader() {
    loader ??= document.getElementById('page-loader');
    return loader;
}

export function showPageLoader() {
    const element = getLoader();

    if (!element) {
        return;
    }

    element.hidden = false;
    element.classList.add('is-active');
    element.setAttribute('aria-hidden', 'false');
}

export function hidePageLoader() {
    const element = getLoader();

    if (!element) {
        return;
    }

    element.classList.remove('is-active');
    element.hidden = true;
    element.setAttribute('aria-hidden', 'true');
}

function isModifiedClick(event) {
    return event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;
}

function isInternalNavigation(anchor) {
    if (!anchor || anchor.hasAttribute('download') || anchor.target === '_blank' || anchor.matches('[aria-disabled="true"], [data-no-page-loader]')) {
        return false;
    }

    const href = anchor.getAttribute('href');

    if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
        return false;
    }

    try {
        return new URL(anchor.href, window.location.href).origin === window.location.origin;
    } catch (error) {
        return false;
    }
}

if (!window.__sipakarbunPageLoaderInitialized) {
    window.__sipakarbunPageLoaderInitialized = true;

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || isModifiedClick(event)) {
            return;
        }

        const anchor = event.target instanceof Element ? event.target.closest('a') : null;

        if (isInternalNavigation(anchor)) {
            showPageLoader();
        }
    });

    document.addEventListener('submit', (event) => {
        if (event.defaultPrevented || !(event.target instanceof HTMLFormElement)) {
            return;
        }

        const form = event.target;

        // A confirmation form is handled by the explicit navigation-start
        // signal after the user accepts it. Never react to its intercepted
        // submit event here.
        if (form.matches('[data-confirm-message]') || form.matches('[data-no-page-loader]') || form.target === '_blank') {
            return;
        }

        showPageLoader();
    });

    window.addEventListener('sipakarbun:navigation-start', (event) => {
        if (event.detail?.form instanceof HTMLFormElement) {
            showPageLoader();
        }
    });

    window.addEventListener('sipakarbun:navigation-cancelled', hidePageLoader);
    window.addEventListener('pageshow', hidePageLoader);
}
