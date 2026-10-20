export type ApplicationCategory = { id: number; name: string };

export type ApplicationForm = {
    name: string;
    description: string;
    url: string;
    icon: string;
    is_active: boolean;
    category: string;
    required_permission: string;
    hydra_client_id: string;
};

export const emptyApplicationForm = (): ApplicationForm => ({
    name: '',
    description: '',
    url: '',
    icon: '',
    is_active: true,
    category: '',
    required_permission: '',
    hydra_client_id: '',
});
