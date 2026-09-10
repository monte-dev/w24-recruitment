import { ref } from 'vue';
import axios from 'axios';

export function useImports() {
    const loading = ref(false);
    const error = ref(null);
    const validationErrors = ref({});

    const clearErrors = () => {
        error.value = null;
        validationErrors.value = {};
    };

    const uploadImport = async (file) => {
        loading.value = true;
        clearErrors();

        try {
            const formData = new FormData();
            formData.append('file', file);

            const response = await axios.post('/api/imports', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'Accept': 'application/json',
                },
            });

            return response.data.data;
        } catch (err) {
            if (err.response?.status === 422) {
                if (err.response.data?.errors) {
                    validationErrors.value = err.response.data.errors;
                }
                error.value = err.response.data?.message || 'Błąd walidacji pliku.';
            } else if (err.response?.data?.message) {
                error.value = err.response.data.message;
            } else {
                error.value = 'Wystąpił nieoczekiwany błąd podczas przesyłania pliku.';
            }
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const fetchImports = async () => {
        loading.value = true;
        clearErrors();

        try {
            const response = await axios.get('/api/imports', {
                headers: {
                    'Accept': 'application/json',
                },
            });
            return response.data.data;
        } catch (err) {
            error.value = err.response?.data?.message || 'Nie udało się pobrać listy importów.';
            throw err;
        } finally {
            loading.value = false;
        }
    };

    const fetchImport = async (id) => {
        loading.value = true;
        clearErrors();

        try {
            const response = await axios.get(`/api/imports/${id}`, {
                headers: {
                    'Accept': 'application/json',
                },
            });
            return response.data.data;
        } catch (err) {
            if (err.response?.status === 404) {
                error.value = 'Import o podanym identyfikatorze nie istnieje.';
            } else {
                error.value = err.response?.data?.message || 'Nie udało się pobrać szczegółów importu.';
            }
            throw err;
        } finally {
            loading.value = false;
        }
    };

    return {
        loading,
        error,
        validationErrors,
        clearErrors,
        uploadImport,
        fetchImports,
        fetchImport,
    };
}
