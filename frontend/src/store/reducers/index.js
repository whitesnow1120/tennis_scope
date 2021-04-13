import {
  GET_ACCOUNT_INFO,
  GET_USER_STATUS,
} from '../actions/types';

const initialState = {
  userLoggedIn: false,
  accountInfo: {
    name: 'Andrejs',
    surname: 'Kosmoss',
    mail: 'andraj@banana.com',
    showTooltips: false,
    subscriptionNotifications: true,
    newFeatures: false,
    promotionNotifications: true,
    subscriptionPlan: 0,
  },
};

const reducer = (state = initialState, action) => {
  const { type, payload } = action;
  switch (type) {
    case GET_ACCOUNT_INFO:
      return { ...state, accountInfo: payload };
    case GET_USER_STATUS:
      return { ...state, userLoggedIn: payload };
    default:
      return initialState;
  }
};

export default reducer;
