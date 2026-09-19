const eventListeners: Record<string, ((e: any) => void)[]> = {};

// Polyfill window and DOM APIs for headless Bun test runner
if (typeof window === 'undefined') {
    // @ts-expect-error Global test environment setup
    globalThis.window = {
        navigator: { userAgent: 'BunTest' },
        location: { href: 'http://localhost' },
        history: {
            scrollRestoration: 'auto',
            state: null,
            pushState: () => {},
            replaceState: () => {},
        },
        matchMedia: (query: string) => ({
            matches: false,
            media: query,
            addEventListener: () => {},
            removeEventListener: () => {},
        }),
        addEventListener: (type: string, listener: any) => {
            eventListeners[type] = eventListeners[type] || [];
            eventListeners[type].push(listener);
        },
        removeEventListener: (type: string, listener: any) => {
            if (eventListeners[type]) {
                eventListeners[type] = eventListeners[type].filter((l) => l !== listener);
            }
        },
        dispatchEvent: (event: any) => {
            const listeners = eventListeners[event.type] || [];
            for (const l of listeners) {
                l(event);
            }
            return true;
        },
    };
} else {
    if (!window.navigator) {
        // @ts-expect-error Global test environment setup
        window.navigator = { userAgent: 'BunTest' };
    }
    if (!window.history) {
        // @ts-expect-error Global test environment setup
        window.history = {
            scrollRestoration: 'auto',
            state: null,
            pushState: () => {},
            replaceState: () => {},
        } as any;
    }
    if (!window.matchMedia) {
        // @ts-expect-error Global test environment setup
        window.matchMedia = (query: string) => ({
            matches: false,
            media: query,
            addEventListener: () => {},
            removeEventListener: () => {},
        }) as any;
    }
}

if (typeof CustomEvent === 'undefined') {
    // @ts-expect-error Global test environment setup
    globalThis.CustomEvent = class CustomEvent {
        type: string;
        detail: any;
        constructor(type: string, options: any = {}) {
            this.type = type;
            this.detail = options?.detail;
        }
    };
}

if (typeof localStorage === 'undefined') {
    const store: Record<string, string> = {};
    // @ts-expect-error Global test environment setup
    globalThis.localStorage = {
        getItem: (k: string) => store[k] ?? null,
        setItem: (k: string, v: string) => {
            store[k] = String(v);
        },
        removeItem: (k: string) => {
            delete store[k];
        },
        clear: () => {
            for (const k in store) delete store[k];
        },
        length: 0,
        key: () => null,
    };
}

if (typeof document === 'undefined') {
    const classSet = new Set<string>();
    const docListeners: Record<string, ((e: any) => void)[]> = {};
    // @ts-expect-error Global test environment setup
    globalThis.document = {
        hidden: false,
        addEventListener: (type: string, listener: any) => {
            docListeners[type] = docListeners[type] || [];
            docListeners[type].push(listener);
        },
        removeEventListener: (type: string, listener: any) => {
            if (docListeners[type]) {
                docListeners[type] = docListeners[type].filter((l) => l !== listener);
            }
        },
        dispatchEvent: (event: any) => {
            const listeners = docListeners[event.type] || [];
            for (const l of listeners) {
                l(event);
            }
            return true;
        },
        createElement: (tag: string) => ({
            tagName: tag.toUpperCase(),
            classList: {
                add: () => {},
                remove: () => {},
                contains: () => false,
            },
            setAttribute: () => {},
            getAttribute: () => null,
            appendChild: () => {},
            removeChild: () => {},
        }),
        documentElement: {
            classList: {
                add: (c: string) => classSet.add(c),
                remove: (c: string) => classSet.delete(c),
                contains: (c: string) => classSet.has(c),
            },
        },
    };
}
