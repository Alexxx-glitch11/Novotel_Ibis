@php
    $isEn = app()->getLocale() === 'en';
@endphp

<style>
    @keyframes livePulseRed {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, .6); }
        70% { box-shadow: 0 0 0 7px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    @keyframes livePulseGreen {
        0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, .6); }
        70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
        100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
    }
    .live-topbar-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #047857;
        font-size: 11px;
        font-weight: 600;
        line-height: 1;
        white-space: nowrap;
    }
    .live-topbar-dot {
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: #10b981;
        animation: livePulseGreen 2s infinite;
        flex-shrink: 0;
    }
    .live-nav-dot {
        margin-left: auto;
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #ef4444;
        animation: livePulseRed 1.8s infinite;
        flex-shrink: 0;
    }
    .live-nav-dot[hidden] { display: none !important; }
    .live-nav-badge {
        margin-left: auto;
        min-width: 19px;
        height: 19px;
        padding: 0 6px;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 10.5px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        flex-shrink: 0;
    }
    .live-nav-badge[hidden] { display: none !important; }
    .live-sync-toast {
        position: fixed;
        right: 20px;
        bottom: 20px;
        z-index: 75;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 15px;
        border-radius: 12px;
        background: #1e2256;
        color: #fff;
        font-size: 12.5px;
        font-weight: 600;
        box-shadow: 0 12px 32px rgba(15, 23, 42, .24);
        transition: opacity .2s ease, transform .2s ease;
    }
    .live-sync-toast[hidden] { display: none !important; }
</style>

<div id="live-sync-toast" class="live-sync-toast" hidden role="status" aria-live="polite">
    <span class="live-topbar-dot" aria-hidden="true"></span>
    <span id="live-sync-toast-text">{{ $isEn ? 'New data received — updated automatically' : 'Data baru masuk — diperbarui otomatis' }}</span>
</div>

<script>
(() => {
    const endpoint = @json($liveEndpoint);
    const role = @json($liveRole);
    const activeChannel = @json($liveActiveChannel ?? '');
    const autoRefresh = @json((bool) ($liveAutoRefresh ?? false));

    if (!endpoint) return;

    const storagePrefix = `dinea_seen_${role}_`;
    const toast = document.getElementById('live-sync-toast');

    let baselineOverall = null;
    let latestChannels = {};
    let pendingSwap = false;
    let isFetching = false;
    let stopped = false;
    let toastTimer = null;

    const buildPollUrl = () => {
        const url = new URL(endpoint, window.location.origin);
        const currentParams = new URLSearchParams(window.location.search);
        const outlet = currentParams.get('outlet');
        if (outlet && outlet !== 'all') {
            url.searchParams.set('outlet', outlet);
        }
        url.searchParams.set('_t', String(Date.now()));
        return url.toString();
    };

    const getSeen = (ch) => {
        try { return window.localStorage.getItem(storagePrefix + ch); }
        catch { return null; }
    };

    const setSeen = (ch, sig) => {
        if (!ch || typeof sig !== 'string') return;
        try { window.localStorage.setItem(storagePrefix + ch, sig); }
        catch { /* ignore storage restrictions */ }
    };

    const showToast = (msg) => {
        if (!toast) return;
        const textEl = document.getElementById('live-sync-toast-text');
        if (textEl && msg) textEl.textContent = msg;
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { toast.hidden = true; }, 3500);
    };

    const renderNavIndicators = (channels) => {
        if (!channels || typeof channels !== 'object') return;

        Object.entries(channels).forEach(([ch, info]) => {
            const sig = String(info?.signature ?? '');
            const pendingCount = Number(info?.pending_count ?? 0);

            if (getSeen(ch) === null || ch === activeChannel) {
                setSeen(ch, sig);
            }

            const pendingBadge = document.querySelector(`[data-live-pending="${ch}"]`);
            const dotEl = document.querySelector(`[data-live-dot="${ch}"]`);
            const hasUnseenUpdate = sig !== '' && getSeen(ch) !== sig && ch !== activeChannel;

            if (pendingBadge) {
                if (pendingCount > 0) {
                    pendingBadge.textContent = pendingCount > 99 ? '99+' : String(pendingCount);
                    pendingBadge.hidden = false;
                    if (dotEl) dotEl.hidden = true;
                    return;
                }
                pendingBadge.hidden = true;
            }

            if (dotEl) {
                dotEl.hidden = !hasUnseenUpdate;
            }
        });
    };

    const isSafeToSwapMain = () => {
        const main = document.querySelector('main');
        if (!main) return false;

        const active = document.activeElement;
        if (active && main.contains(active)) {
            const tag = active.tagName;
            if (tag === 'TEXTAREA' || tag === 'SELECT') return false;
            if (tag === 'INPUT' && !['button', 'submit', 'reset'].includes((active.type || '').toLowerCase())) {
                return false;
            }
        }

        if (document.querySelector('#cashier-qr-modal:not([hidden])')) return false;
        if (document.querySelector('#finance-proof-modal.flex')) return false;
        if (document.querySelector('#ar-camera:not([hidden]), #mr-camera:not([hidden])')) return false;
        if (document.querySelector('#menu-form:not([hidden]), #category-form:not([hidden])')) return false;

        // Protect unsaved user input in POST forms
        const dirtyInput = Array.from(
            main.querySelectorAll('form[method="post" i] textarea, form[method="post" i] input[name="notes"]')
        ).some(el => String(el.value || '').trim() !== '');
        if (dirtyInput) return false;

        return true;
    };

    const swapMainDom = async (newChannels) => {
        if (isFetching || stopped) return;
        if (!isSafeToSwapMain()) {
            pendingSwap = true;
            return;
        }

        isFetching = true;
        const targetUrl = window.location.href;
        const startPath = window.location.pathname;

        try {
            const res = await fetch(targetUrl, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'X-Live-Refresh': '1',
                    'Accept': 'text/html',
                },
            });

            if (res.status === 401 || res.status === 403 || res.redirected && res.url.includes('/login')) {
                stopped = true;
                return;
            }
            if (!res.ok || window.location.pathname !== startPath) return;

            const html = await res.text();
            const parsed = new DOMParser().parseFromString(html, 'text/html');
            const freshMain = parsed.querySelector('main');
            const currentMain = document.querySelector('main');

            if (!freshMain || !currentMain) return;

            const prevScrollY = window.scrollY;
            currentMain.innerHTML = freshMain.innerHTML;
            window.scrollTo({ top: prevScrollY, behavior: 'instant' });

            pendingSwap = false;
            if (activeChannel && newChannels[activeChannel]) {
                setSeen(activeChannel, String(newChannels[activeChannel].signature ?? ''));
            }
            renderNavIndicators(newChannels);
            showToast(document.documentElement.lang === 'en'
                ? 'New data received — updated automatically'
                : 'Data baru masuk — diperbarui otomatis');
            window.dispatchEvent(new CustomEvent('dinea:live-updated', { detail: { channels: newChannels } }));
        } catch {
            // Transient network glitch: wait for next poll cycle
        } finally {
            isFetching = false;
        }
    };

    const poll = async () => {
        if (stopped || isFetching || document.visibilityState === 'hidden') return;

        try {
            const res = await fetch(buildPollUrl(), {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json' },
            });

            if (res.status === 401 || res.status === 403 || (res.redirected && res.url.includes('/login'))) {
                stopped = true;
                return;
            }
            if (!res.ok) return;

            const ct = res.headers.get('content-type') || '';
            if (!ct.includes('application/json')) return;

            const data = await res.json();
            latestChannels = data.channels || {};
            renderNavIndicators(latestChannels);

            const nextOverall = String(data.overall_signature || '');
            if (baselineOverall === null) {
                baselineOverall = nextOverall;
                return;
            }

            if ((nextOverall !== '' && nextOverall !== baselineOverall) || pendingSwap) {
                baselineOverall = nextOverall;
                if (autoRefresh) {
                    await swapMainDom(latestChannels);
                } else if (nextOverall !== '') {
                    pendingSwap = false;
                }
            }
        } catch {
            // Silent retry next interval
        }
    };

    document.addEventListener('click', (e) => {
        const navLink = e.target.closest?.('[data-live-nav]');
        if (!navLink) return;
        const ch = navLink.getAttribute('data-live-nav');
        if (ch && latestChannels[ch]) {
            setSeen(ch, String(latestChannels[ch].signature ?? ''));
            renderNavIndicators(latestChannels);
        }
    });

    document.addEventListener('focusout', () => {
        if (pendingSwap && autoRefresh) {
            setTimeout(() => {
                if (pendingSwap && isSafeToSwapMain()) {
                    swapMainDom(latestChannels);
                }
            }, 250);
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            poll();
        }
    });

    poll();
    window.setInterval(poll, 4500);
})();
</script>
