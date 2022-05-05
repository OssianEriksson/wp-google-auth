import { render } from '@wordpress/element';

import SettingsPage, { Role } from '../components/settings-page';

declare const wpGoogleAuth: {
	roles: Role[];
};

document.addEventListener('DOMContentLoaded', () => {
	const root = document.getElementById('ftek-google-auth-settings');
	if (root) {
		render(<SettingsPage availableRoles={wpGoogleAuth.roles} />, root);
	}
});
