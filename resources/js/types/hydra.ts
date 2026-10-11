export type HydraClientForm = {
    client_name: string;
    client_secret: string;
    redirect_uris: string;
    post_logout_redirect_uris: string;
    grant_types: string;
    response_types: string;
    scope: string;
    audience: string;
    allowed_cors_origins: string;
    contacts: string;
    token_endpoint_auth_method: string;
    owner: string;
    client_uri: string;
    logo_uri: string;
    policy_uri: string;
    tos_uri: string;
    skip_consent: boolean;
    skip_logout_consent: boolean;
};

export const emptyHydraClientForm = (): HydraClientForm => ({
    client_name: '',
    client_secret: '',
    redirect_uris: '',
    post_logout_redirect_uris: '',
    grant_types: 'authorization_code\nrefresh_token',
    response_types: 'code',
    scope: 'openid profile email',
    audience: '',
    allowed_cors_origins: '',
    contacts: '',
    token_endpoint_auth_method: 'client_secret_basic',
    owner: '',
    client_uri: '',
    logo_uri: '',
    policy_uri: '',
    tos_uri: '',
    skip_consent: false,
    skip_logout_consent: false,
});

export const updateHydraClientForm = (
    form: HydraClientForm,
    field: keyof HydraClientForm,
    value: string | boolean,
) => {
    if (field === 'skip_consent') {
        if (typeof value !== 'boolean') {
            throw new TypeError('Expected a boolean value for skip_consent.');
        }

        form.skip_consent = value;

        return;
    }

    if (field === 'skip_logout_consent') {
        if (typeof value !== 'boolean') {
            throw new TypeError(
                'Expected a boolean value for skip_logout_consent.',
            );
        }

        form.skip_logout_consent = value;

        return;
    }

    if (typeof value !== 'string') {
        throw new TypeError(`Expected a string value for ${field}.`);
    }

    form[field] = value;
};
