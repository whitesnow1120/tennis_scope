import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8001/api',
  // baseURL: 'http://162.0.216.56:80/v1/api',
});

export default api;
