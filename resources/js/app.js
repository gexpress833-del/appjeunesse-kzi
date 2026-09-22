const themeStorageKey = 'appjeunesse-theme';
const storedTheme = window.localStorage.getItem(themeStorageKey);
const preferredTheme = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
const FCM_TOKEN_STORAGE_KEY = 'appjeunesse-fcm-token';

const firebaseConfig = {
	apiKey: window.__APP_FIREBASE_CONFIG__?.apiKey || '',
	authDomain: window.__APP_FIREBASE_CONFIG__?.authDomain || '',
	projectId: window.__APP_FIREBASE_CONFIG__?.projectId || '',
	messagingSenderId: window.__APP_FIREBASE_CONFIG__?.messagingSenderId || '',
	appId: window.__APP_FIREBASE_CONFIG__?.appId || '',
};

const showAppToast = (title, body, type = 'info') => {
	const container = document.getElementById('app-toast-stack');

	if (!container) {
		return;
	}

	const toast = document.createElement('div');
	const toneClasses = {
		info: 'border-cyan-400/40 bg-slate-900/90 text-slate-50',
		success: 'border-emerald-400/40 bg-emerald-500/10 text-emerald-100',
		warning: 'border-amber-400/40 bg-amber-500/10 text-amber-100',
		error: 'border-rose-400/40 bg-rose-500/10 text-rose-100',
	};

	toast.className = [
		'pointer-events-auto',
		'rounded-2xl',
		'border',
		'border-white/10',
		'backdrop-blur-xl',
		'shadow-2xl',
		'shadow-slate-950/30',
		'p-4',
		'animate-[fadeIn_0.2s_ease-out]',
		toneClasses[type] || toneClasses.info,
	].join(' ');

	const content = document.createElement('div');
	content.className = 'flex items-start gap-3';
	content.innerHTML = `
		<div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white/10 text-lg">${type === 'success' ? '✓' : type === 'warning' ? '!' : type === 'error' ? '✕' : '🔔'}</div>
		<div class="min-w-0 flex-1"><p class="text-sm font-bold leading-5"></p><p class="mt-1 text-sm text-slate-300/90"></p></div>
		<button type="button" class="ml-2 rounded-lg border border-white/10 bg-white/5 px-2 py-1 text-xs font-semibold text-slate-200 transition hover:bg-white/10" aria-label="Fermer la notification">×</button>
	`;
	content.querySelector('p:first-of-type').textContent = title;
	content.querySelector('p:last-of-type').textContent = body;
	toast.appendChild(content);

	toast.querySelector('button').addEventListener('click', () => {
		toast.remove();
	});

	container.appendChild(toast);
	window.setTimeout(() => {
		toast.style.opacity = '0';
		toast.style.transform = 'translateY(-0.5rem)';
		toast.style.transition = 'all 0.2s ease';
		window.setTimeout(() => toast.remove(), 200);
	}, 5000);
};

window.showAppToast = showAppToast;

document.querySelectorAll('[data-app-flash]').forEach((message) => {
	showAppToast(message.dataset.appFlashTitle || 'Succès', message.textContent.trim(), message.dataset.appFlashType || 'success');
	message.remove();
});

const registerFcmToken = async (token, device = 'web') => {
	try {
		const response = await fetch('/notifications/fcm/register', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
			},
			body: JSON.stringify({ token, device }),
		});

		if (response.ok) {
			window.localStorage.setItem(FCM_TOKEN_STORAGE_KEY, token);
			return;
		}

		console.warn('FCM registration rejected', response.status, await response.text());
	} catch (error) {
		console.warn('FCM registration failed', error);
	}
};

const requestFcmPermission = async () => {
	if (!('Notification' in window) || !('serviceWorker' in navigator)) {
		showAppToast('Notifications indisponibles', 'Ce navigateur ou cette PWA ne prend pas en charge les notifications push.', 'warning');
		return;
	}

	if (!firebaseConfig.apiKey || !firebaseConfig.projectId || !firebaseConfig.messagingSenderId || !firebaseConfig.appId || !window.__APP_FIREBASE_CONFIG__?.vapidKey) {
		showAppToast('Configuration incomplète', 'La configuration Firebase des notifications est absente ou incomplète.', 'error');
		return;
	}

	try {
		const { initializeApp } = await import('firebase/app');
		const { getMessaging, getToken, onMessage } = await import('firebase/messaging');
		const app = initializeApp(firebaseConfig);
		const messaging = getMessaging(app);

		if (Notification.permission === 'default') {
			const permission = await Notification.requestPermission();

			if (permission !== 'granted') {
				return;
			}
		}

		if (Notification.permission !== 'granted') {
			showAppToast('Notifications désactivées', 'Autorisez les notifications dans les réglages du navigateur.', 'warning');
			return;
		}

		const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js', { updateViaCache: 'none' });
		const token = await getToken(messaging, { vapidKey: window.__APP_FIREBASE_CONFIG__?.vapidKey || '', serviceWorkerRegistration: registration });

		if (token) {
			await registerFcmToken(token, 'web');
		}

		onMessage(messaging, (payload) => {
			const title = payload.notification?.title || 'Nouvelle notification';
			const body = payload.notification?.body || 'Vous avez un nouveau message.';

			showAppToast(title, body, 'info');
		});
	} catch (error) {
		console.warn('Unable to initialize Firebase messaging', error);
	}
};

const updateNotificationButtons = () => {
	document.querySelectorAll('[data-notifications-enable]').forEach((button) => {
		button.hidden = 'Notification' in window && Notification.permission === 'granted';
	});
};

const bindNotificationButtons = () => {
	document.querySelectorAll('[data-notifications-enable]').forEach((button) => {
		if (button.dataset.notificationsBound === 'true') {
			return;
		}

		button.dataset.notificationsBound = 'true';
		button.addEventListener('click', async () => {
			button.disabled = true;
			button.setAttribute('aria-busy', 'true');
			await requestFcmPermission();
			button.disabled = false;
			button.removeAttribute('aria-busy');
			updateNotificationButtons();
		});
	});

	updateNotificationButtons();
};

document.documentElement.dataset.theme = storedTheme || preferredTheme;

let deferredInstallPrompt;

const isInstalledApp = () => window.matchMedia('(display-mode: standalone)').matches
	|| window.navigator.standalone === true;

const hideInstallButtons = () => {
	document.querySelectorAll('[data-app-install]').forEach((button) => button.setAttribute('hidden', 'hidden'));
};

if (isInstalledApp()) {
	hideInstallButtons();
}

const showInstallPrompt = () => {
	const installButtons = document.querySelectorAll('[data-app-install]');

	if (!installButtons.length || isInstalledApp()) {
		return;
	}

	installButtons.forEach((installButton) => {
		installButton.hidden = false;

		if (installButton.dataset.installBound === 'true') {
			return;
		}

		installButton.dataset.installBound = 'true';
		installButton.addEventListener('click', async () => {
			if (!deferredInstallPrompt) {
				window.alert('Pour installer l’application, ouvrez le menu de votre navigateur puis choisissez « Ajouter à l’écran d’accueil ».');
				return;
			}

			deferredInstallPrompt.prompt();
			await deferredInstallPrompt.userChoice;
			deferredInstallPrompt = null;
		});
	});
};

if ('serviceWorker' in navigator) {
	window.addEventListener('load', async () => {
		navigator.serviceWorker.register('/sw.js').catch(() => {});
		bindNotificationButtons();
		if ('Notification' in window && Notification.permission === 'granted') {
			await requestFcmPermission();
		}
		navigator.serviceWorker.addEventListener('message', (event) => {
			const payload = event.data;
			if (!payload || payload.type !== 'app-push') {
				return;
			}

			showAppToast(payload.title || 'Nouvelle notification', payload.body || 'Vous avez un nouveau message.', 'info');
		});
	});
}

window.addEventListener('beforeinstallprompt', (event) => {
	event.preventDefault();
	deferredInstallPrompt = event;
	showInstallPrompt();
});

window.addEventListener('appinstalled', () => {
	deferredInstallPrompt = null;
	hideInstallButtons();
});

document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
	const updateLabel = () => {
		const isLight = document.documentElement.dataset.theme === 'light';
		toggle.setAttribute('aria-label', isLight ? 'Activer le mode sombre' : 'Activer le mode clair');
		toggle.querySelector('[data-theme-icon]').textContent = isLight ? '☾' : '☀';
		toggle.querySelector('[data-theme-label]').textContent = isLight ? 'Sombre' : 'Clair';
	};

	toggle.addEventListener('click', () => {
		const nextTheme = document.documentElement.dataset.theme === 'light' ? 'dark' : 'light';

		document.documentElement.dataset.theme = nextTheme;
		window.localStorage.setItem(themeStorageKey, nextTheme);
		updateLabel();
	});

	window.toggleSidebar = () => {

		const sidebar = document.getElementById('sidebar');
		const backdrop = document.getElementById('sidebar-backdrop');

		if (!sidebar || !backdrop) {
			return;
		}

		const isClosed = sidebar.classList.toggle('sidebar-closed');

		sidebar.classList.toggle('sidebar-open', !isClosed);
		backdrop.classList.toggle('hidden', isClosed);
	};

	window.closeSidebar = () => {
		const sidebar = document.getElementById('sidebar');
		const backdrop = document.getElementById('sidebar-backdrop');

		if (!sidebar || !backdrop) {
			return;
		}

		sidebar.classList.add('sidebar-closed');
		sidebar.classList.remove('sidebar-open');
		backdrop.classList.add('hidden');
	};

	updateLabel();
});

showInstallPrompt();

const isNavigableLink = (link, event) => {
	const href = link.getAttribute('href');

	return href
		&& href !== '#'
		&& !href.startsWith('#')
		&& !href.startsWith('mailto:')
		&& !href.startsWith('tel:')
		&& !link.hasAttribute('download')
		&& link.dataset.noLoading === undefined
		&& !link.hasAttribute('target')
		&& !event.defaultPrevented
		&& event.button === 0
		&& !event.metaKey
		&& !event.ctrlKey
		&& !event.shiftKey
		&& !event.altKey;
};

const clickTargetsSelector = 'a, button, input[type="submit"], input[type="button"], [role="button"], summary, label[for], .clickable';

const applyPressedState = (element) => {
	if (!element || element.dataset.noFeedback !== undefined || element.closest('[data-no-feedback]')) {
		return;
	}

	element.classList.add('action-pressed');
	window.setTimeout(() => element.classList.remove('action-pressed'), 220);
};

document.addEventListener('pointerdown', (event) => {
	const target = event.target.closest(clickTargetsSelector);

	if (!target || target.dataset.noFeedback !== undefined || target.closest('[data-no-feedback]')) {
		return;
	}

	applyPressedState(target);
});

document.addEventListener('click', (event) => {
	const link = event.target.closest('a');

	if (!link || !isNavigableLink(link, event) || link.dataset.loading === 'true') {
		return;
	}

	link.dataset.loading = 'true';
	link.setAttribute('aria-busy', 'true');
	link.classList.add('action-loading');

	if (typeof window.closeSidebar === 'function' && window.innerWidth < 1024) {
		window.closeSidebar();
	}
});

document.addEventListener('click', (event) => {
	const actionTarget = event.target.closest(clickTargetsSelector);

	if (!actionTarget || actionTarget.dataset.noFeedback !== undefined || actionTarget.closest('[data-no-feedback]')) {
		return;
	}

	if (actionTarget.matches('button') && actionTarget.type === 'button') {
		applyPressedState(actionTarget);
		return;
	}

	if (actionTarget.matches('a, input[type="submit"], input[type="button"], [role="button"], summary, label[for], .clickable')) {
		applyPressedState(actionTarget);
	}
});

document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {

	const password = document.getElementById(toggle.dataset.passwordToggle);

	if (!password) {
		return;
	}

	toggle.addEventListener('click', () => {
		const isVisible = password.type === 'text';

		password.type = isVisible ? 'password' : 'text';
		toggle.textContent = isVisible ? '👁' : '🙈';
		toggle.setAttribute('aria-label', isVisible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
	});
});

document.querySelectorAll('form').forEach((form) => {
	form.addEventListener('submit', (event) => {
		if (form.matches('[data-video-comment-form], [data-video-like-form]')) {
			return;
		}

		if (form.dataset.loading === 'true') {
			event.preventDefault();
			return;
		}

		const submitter = event.submitter || form.querySelector('button[type="submit"], button:not([type])');

		if (!submitter || submitter.dataset.noLoading !== undefined) {
			return;
		}

		form.dataset.loading = 'true';
		submitter.disabled = true;
		submitter.setAttribute('aria-busy', 'true');
		submitter.innerHTML = '<span class="loading-spinner" aria-hidden="true"></span><span>Chargement...</span>';
	});
});
