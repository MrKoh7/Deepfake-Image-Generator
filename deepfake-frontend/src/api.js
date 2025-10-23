import axios from 'axios'

axios.defaults.baseURL = "http://127.0.0.1:8000/api";
axios.defaults.withCredentials = true; // include cookies

export const api = axios.create({
  baseURL: 'http://127.0.0.1:8000/api',
  withCredentials: true
});

// Add CSRF init
export async function initCsrf() {
  await axios.get('http://127.0.0.1:8000/sanctum/csrf-cookie', {
    withCredentials: true
  });
}

export const downloadZip = async (files) => {
  const response = await api.post(
    '/download-zip',
    { files },
    { responseType: 'blob' }
  )
  return response
}
