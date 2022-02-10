/*
WP Google Auth
Copyright (C) 2022  Ossian Eriksson

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

import { render, useState, useEffect } from "@wordpress/element";
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
} from "@wordpress/components";
import { store as noticesStore } from "@wordpress/notices";
import { __ } from "@wordpress/i18n";
import { useDispatch, useSelect } from "@wordpress/data";
import apiFetch from "@wordpress/api-fetch";
import { trash, menu } from "@wordpress/icons";
import structuredClone from "@ungap/structured-clone";

import "./settings.scss";

type RoleKey = string;
type Role = {
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
        {__("The following error has occurred:", "wp-google-auth")}
        <pre className="error">{JSON.stringify(error, null, 4)}</pre>
    </>
);

const NoticeBar = (): JSX.Element => {
    const notices = useSelect((select) =>
        select(noticesStore).getNotices()
    ).filter((notice) => notice.type === "snackbar");
    const { removeNotice } = useDispatch(noticesStore);
    return <SnackbarList notices={notices} onRemove={removeNotice} />;
};

const SpinnerPlaceholder = (): JSX.Element => (
    <Placeholder>
        <div className="placeholder-center">
            <Spinner />
        </div>
    </Placeholder>
);

const EmailPatternSelector = ({
    pattern,
    onDelete,
    onPatternChange,
}: {
    pattern: EmailPattern;
    onDelete: () => void;
    onPatternChange: (pattern: EmailPattern) => void;
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

    const avaliableRoles = wp_google_auth.roles as Role[];
    return (
        <div className="email-pattern-row">
            <div className="email-pattern-row-button">
                <Button onClick={onDelete} isSecondary>
                    <Icon icon={trash} size={24} />
                </Button>
            </div>
            <div className="email-pattern-row-center">
                <TextControl
                    label={__("Email regex pattern", "wp-google-auth")}
                    onChange={(value: string) =>
                        onPatternChange({
                            regex: value,
                            roles: pattern.roles,
                        })
                    }
                    value={pattern.regex}
                />
            </div>
            <div className="email-pattern-row-button">
                <DropdownMenu
                    icon={menu}
                    label={__("Select roles", "wp-google-auth")}
                >
                    {({ onClose }) =>
                        avaliableRoles.map((role, i) => (
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

const SettingsContent = (): JSX.Element => {
    const [error, setError] = useState<unknown>(null);
    const [option, setOption] = useState<Option>(null);
    useEffect(() => {
        apiFetch({ path: "/wp/v2/settings" })
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
            createNotice("error", reason?.message || JSON.stringify(reason), {
                type: "snackbar",
            });

        apiFetch({
            path: `/wp-google-auth/v1/validate/oauth-client?client_id=${option.client_id}&client_secret=${option.client_secret}`,
        })
            .then((res) => {
                const oauthClientErrors = res as string[];
                if (oauthClientErrors?.length > 0) {
                    oauthClientErrors.forEach((error) =>
                        createNotice("error", error, {
                            type: "snackbar",
                            isDismissible: false,
                        })
                    );
                }

                apiFetch({
                    path: "/wp/v2/settings",
                    method: "POST",
                    data: { wp_google_auth_option: option },
                })
                    .then(() => {
                        if (oauthClientErrors?.length <= 0) {
                            createNotice(
                                "success",
                                __(
                                    "Settings saved! Please test out the login functionality to verify that everything is working as expected.",
                                    "wp-google-auth"
                                ),
                                { type: "snackbar" }
                            );
                        }
                    })
                    .catch(displayError);
            })
            .catch(displayError);
    };

    return (
        <>
            <h2>{__("OAuth Client", "wp-google-auth")}</h2>
            <TextControl
                label={__("Google OAuth client ID", "wp-google-auth")}
                value={option.client_id}
                onChange={(value: string) =>
                    setOption({ ...option, client_id: value })
                }
            />
            <TextControl
                label={__("Google OAuth client secret", "wp-google-auth")}
                value={option.client_secret}
                onChange={(value: string) =>
                    setOption({ ...option, client_secret: value })
                }
            />
            <h2>{__("Account Settings", "wp-google-auth")}</h2>
            <p>
                {__(
                    "Here you can enter regex pattern to be matched against user emails. For every match, you can select which roles should be applied to the user.",
                    "wp-google-auth"
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
                />
            ))}
            <Button
                onClick={() => {
                    const newOption = structuredClone(option);
                    newOption.email_patterns.push({
                        regex: "",
                        roles: [],
                    });
                    setOption(newOption);
                }}
                isSecondary
            >
                {__("Add pattern", "wp-google-auth")}
            </Button>
            <h2>{__("Miscellaneous Settings", "wp-google-auth")}</h2>
            <TextControl
                label={__("Cache refresh interval (hours)", "wp-google-auth")}
                type="number"
                min="0"
                value={option.cache_refresh}
                onChange={(value: any) =>
                    setOption({ ...option, cache_refresh: Number(value) })
                }
            />
            <Button onClick={save} isPrimary>
                {__("Save changes", "wp-google-auth")}
            </Button>
        </>
    );
};

const SettingsPage = (): JSX.Element => (
    <>
        <h1>{__("WP Google Auth Settings", "wp-google-auth")}</h1>
        <SettingsContent />
        <NoticeBar />
    </>
);

document.addEventListener("DOMContentLoaded", () => {
    const root = document.getElementById("wp_google_auth_settings");
    if (root) {
        render(<SettingsPage />, root);
    }
});
