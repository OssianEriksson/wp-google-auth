import { useState, useEffect } from '@wordpress/element';
import {
	Placeholder,
	Spinner,
	TextControl,
	Button,
	SnackbarList,
	Icon,
	DropdownMenu,
	MenuItem,
	CheckboxControl,
} from '@wordpress/components';
import { store as noticesStore } from '@wordpress/notices';
import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import { trash, menu } from '@wordpress/icons';
import structuredClone from '@ungap/structured-clone';

import styles from './index.module.scss';

type RoleKey = string;
export type Role = {
	key: RoleKey;
	name: string;
};

type EmailPattern = {
	regex: string;
	roles: RoleKey[];
};
type Option = {
	client_id: string;
	client_secret: string;
	email_patterns: EmailPattern[];
	cache_refresh: number;
};

type SettingsObject = {
	wp_google_auth_option?: Option;
};

const ErrorDisplay = (error: any): JSX.Element => (
	<>
		{__('The following error has occurred:', 'ftek-google-auth')}
		<pre className={styles['error']}>{JSON.stringify(error, null, 4)}</pre>
	</>
);

const NoticeBar = (): JSX.Element => {
	const notices = useSelect((select) =>
		select(noticesStore).getNotices()
	).filter((notice) => notice.type === 'snackbar');
	const { removeNotice } = useDispatch(noticesStore);
	return <SnackbarList notices={notices} onRemove={removeNotice} />;
};

const SpinnerPlaceholder = (): JSX.Element => (
	<Placeholder>
		<div className={styles['placeholder-center']}>
			<Spinner />
		</div>
	</Placeholder>
);

const EmailPatternSelector = ({
	pattern,
	onDelete,
	onPatternChange,
	availableRoles,
}: {
	pattern: EmailPattern;
	onDelete: () => void;
	onPatternChange: (pattern: EmailPattern) => void;
	availableRoles: Role[];
}): JSX.Element => {
	const toggleRole = (role: RoleKey, enable: boolean) => {
		const index = pattern.roles.indexOf(role);
		const newPattern = structuredClone(pattern);
		if (index >= 0 && !enable) {
			newPattern.roles.splice(index, 1);
		}
		if (index < 0 && enable) {
			newPattern.roles.push(role);
		}
		onPatternChange(newPattern);
	};

	return (
		<div className={styles['email-pattern-row']}>
			<div className={styles['email-pattern-row-button']}>
				<Button onClick={onDelete} isSecondary>
					<Icon icon={trash} size={24} />
				</Button>
			</div>
			<div className={styles['email-pattern-row-center']}>
				<TextControl
					label={__('Email regex pattern', 'ftek-google-auth')}
					onChange={(value: string) =>
						onPatternChange({
							regex: value,
							roles: pattern.roles,
						})
					}
					value={pattern.regex}
				/>
			</div>
			<div className={styles['email-pattern-row-button']}>
				<DropdownMenu
					icon={menu}
					label={__('Select roles', 'ftek-google-auth')}
				>
					{() =>
						availableRoles.map((role, i) => (
							<MenuItem key={`${i}`}>
								<CheckboxControl
									label={role.name}
									checked={pattern.roles.includes(role.key)}
									onChange={(checked: boolean) =>
										toggleRole(role.key, checked)
									}
								/>
							</MenuItem>
						))
					}
				</DropdownMenu>
			</div>
		</div>
	);
};

const SettingsContent = ({
	availableRoles,
}: {
	availableRoles: Role[];
}): JSX.Element => {
	const [error, setError] = useState<unknown>(null);
	const [option, setOption] = useState<Option>(null);
	useEffect(() => {
		apiFetch({ path: '/wp/v2/settings' })
			.then((response: SettingsObject) => {
				setOption(response?.wp_google_auth_option);
			})
			.catch((reason: any) => setError(reason));
	}, []);

	const { createNotice } = useDispatch(noticesStore);

	if (error) {
		return <ErrorDisplay error={error} />;
	}

	if (!option) {
		return <SpinnerPlaceholder />;
	}

	const save = () => {
		const displayError = (reason: any) =>
			createNotice('error', reason?.message || JSON.stringify(reason), {
				type: 'snackbar',
			});

		apiFetch({
			path: `/ftek-google-auth/v1/validate/oauth-client?client_id=${option.client_id}&client_secret=${option.client_secret}`,
		})
			.then((res) => {
				const oauthClientErrors = res as string[];
				if (oauthClientErrors?.length > 0) {
					oauthClientErrors.forEach((clientError) =>
						createNotice('error', clientError, {
							type: 'snackbar',
							isDismissible: false,
						})
					);
				}

				apiFetch({
					path: '/wp/v2/settings',
					method: 'POST',
					data: { wp_google_auth_option: option },
				})
					.then(() => {
						if (oauthClientErrors?.length <= 0) {
							createNotice(
								'success',
								__(
									'Settings saved! Please test out the login functionality to verify that everything is working as expected.',
									'ftek-google-auth'
								),
								{ type: 'snackbar' }
							);
						}
					})
					.catch(displayError);
			})
			.catch(displayError);
	};

	return (
		<>
			<h2>{__('OAuth Client', 'ftek-google-auth')}</h2>
			<TextControl
				label={__('Google OAuth client ID', 'ftek-google-auth')}
				value={option.client_id}
				onChange={(value: string) =>
					setOption({ ...option, client_id: value })
				}
			/>
			<TextControl
				label={__('Google OAuth client secret', 'ftek-google-auth')}
				value={option.client_secret}
				onChange={(value: string) =>
					setOption({ ...option, client_secret: value })
				}
			/>
			<h2>{__('Account Settings', 'ftek-google-auth')}</h2>
			<p>
				{__(
					'Here you can enter regex pattern to be matched against user emails. For every match, you can select which roles should be applied to the user.',
					'ftek-google-auth'
				)}
			</p>
			{option.email_patterns.map((pattern, i) => (
				<EmailPatternSelector
					key={`${i}`}
					pattern={pattern}
					onDelete={() => {
						const newOption = structuredClone(option);
						newOption.email_patterns.splice(i, 1);
						setOption(newOption);
					}}
					onPatternChange={(p) => {
						const newOption = structuredClone(option);
						newOption.email_patterns[i] = p;
						setOption(newOption);
					}}
					availableRoles={availableRoles}
				/>
			))}
			<Button
				onClick={() => {
					const newOption = structuredClone(option);
					newOption.email_patterns.push({
						regex: '',
						roles: [],
					});
					setOption(newOption);
				}}
				isSecondary
			>
				{__('Add pattern', 'ftek-google-auth')}
			</Button>
			<h2>{__('Miscellaneous Settings', 'ftek-google-auth')}</h2>
			<TextControl
				label={__('Cache refresh interval (hours)', 'ftek-google-auth')}
				type="number"
				min="0"
				value={option.cache_refresh}
				onChange={(value: any) =>
					setOption({ ...option, cache_refresh: Number(value) })
				}
			/>
			<Button onClick={save} isPrimary>
				{__('Save changes', 'ftek-google-auth')}
			</Button>
		</>
	);
};

const SettingsPage = ({
	availableRoles,
}: {
	availableRoles: Role[];
}): JSX.Element => (
	<div>
		<h1>{__('Ftek Google Auth Settings', 'ftek-google-auth')}</h1>
		<SettingsContent availableRoles={availableRoles} />
		<NoticeBar />
	</div>
);

export default SettingsPage;
