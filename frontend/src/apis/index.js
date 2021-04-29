import api from '../utils/api';
import { formatDate } from '../utils';

export const login = async (params) => {
  try {
    return await api.post('/user-login', params, {
      headers: {
        'Content-Type': 'application/json',
      },
    });
  } catch (err) {
    console.log('error login');
    return 0;
  }
};

export const getHistoryData = async (date) => {
  try {
    return await api.get('/history', {
      params: {
        date: formatDate(date),
      },
    });
  } catch (err) {
    console.log('error getting the history');
    return 0;
  }
};

export const getUpcomingData = async () => {
  try {
    return await api.get('/upcoming');
  } catch (err) {
    console.log('error getting the upcoming');
    return 0;
  }
};

export const getInplayData = async () => {
  try {
    return await api.get('/inplay');
  } catch (err) {
    console.log('error getting the inplay');
    return 0;
  }
};

export const getInplayScoreData = async () => {
  try {
    return await api.post('/inplay-scores');
  } catch (err) {
    console.log('error getting the inplay scores');
    return 0;
  }
};

export const getRelationData = async (params) => {
  try {
    return await api.get('/relation', {
      params: params,
    });
  } catch (err) {
    console.log('error getting the relation');
    return 0;
  }
};

export const getRobotsData = async () => {
  try {
    return await api.get('/robots');
  } catch (err) {
    console.log('error getting the robots');
    return 0;
  }
};
