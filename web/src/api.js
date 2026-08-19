const BASE = String(import.meta.env.PUBLIC_API_BASE || '').replace(/\/$/, '');
const TOKEN_KEY = 'monitor_auth_token';

export function apiUrl(path) {
  return `${BASE}${path.startsWith('/') ? path : `/${path}`}`;
}

function storage() {
  try {
    return window.localStorage;
  } catch {
    return null;
  }
}

export function getToken() {
  return storage()?.getItem(TOKEN_KEY) || '';
}

export function setToken(token) {
  const store = storage();
  if (!store) {
    return;
  }
  if (!token) {
    store.removeItem(TOKEN_KEY);
    return;
  }
  store.setItem(TOKEN_KEY, token);
}

export function clearToken() {
  storage()?.removeItem(TOKEN_KEY);
}

export async function apiFetch(path, options = {}) {
  const headers = { ...(options.headers || {}) };
  const token = getToken();
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }
  if (options.body && !headers['Content-Type'] && !(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json';
  }

  const response = await fetch(apiUrl(path), { ...options, headers });
  if (response.status === 401 && !path.includes('/login')) {
    clearToken();
    window.dispatchEvent(new Event('monitor:unauthorized'));
  }

  return response;
}
