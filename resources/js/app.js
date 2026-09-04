const themeStorageKey = 'appjeunesse-theme';
const storedTheme = window.localStorage.getItem(themeStorageKey);
const preferredTheme = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';

document.documentElement.dataset.theme = storedTheme || preferredTheme;

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
