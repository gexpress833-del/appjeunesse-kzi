const themeStorageKey = 'appjeunesse-theme';
const storedTheme = window.localStorage.getItem(themeStorageKey);
const preferredTheme = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';

document.documentElement.dataset.theme = storedTheme || preferredTheme;

let deferredInstallPrompt;

const showInstallPrompt = () => {
	const installButtons = document.querySelectorAll('[data-app-install]');

	if (!installButtons.length || !deferredInstallPrompt) {
		return;
	}

	installButtons.forEach((installButton) => {
		installButton.hidden = false;
		installButton.addEventListener('click', async () => {
			deferredInstallPrompt.prompt();
			await deferredInstallPrompt.userChoice;
			deferredInstallPrompt = null;
			installButtons.forEach((button) => { button.hidden = true; });
		}, { once: true });
	});
};

if ('serviceWorker' in navigator) {
	window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}

window.addEventListener('beforeinstallprompt', (event) => {
	event.preventDefault();
	deferredInstallPrompt = event;
	showInstallPrompt();
});

window.addEventListener('appinstalled', () => {
	deferredInstallPrompt = null;
	document.querySelectorAll('[data-app-install]').forEach((button) => button.setAttribute('hidden', 'hidden'));
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
		const isClosed = sidebar.classList.toggle('sidebar-closed');

		sidebar.classList.toggle('sidebar-open', !isClosed);
		backdrop.classList.toggle('hidden', isClosed);
	};

	window.closeSidebar = () => {
		const sidebar = document.getElementById('sidebar');

		sidebar.classList.add('sidebar-closed');
		sidebar.classList.remove('sidebar-open');
		document.getElementById('sidebar-backdrop').classList.add('hidden');
	};

	updateLabel();
});

showInstallPrompt();

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
