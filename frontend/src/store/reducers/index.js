import {
  GET_HISTORY_DATA,
  GET_HISTORY_DATE,
  GET_UPCOMING_DATA,
  GET_RELATION_DATA,
  GET_RELATION_SET_NUMBER,
  GET_RELATION_BREAKS,
  GET_RELATION_ENABLE_OPPONENT_IDS,
  GET_INPLAY_DATA,
  GET_ACCOUNT_INFO,
} from '../actions/types';

const initialState = {
  historyData: [],
  upcomingData: [],
  inplayData: [],
  relationData: {},
  setNumber: {},
  breaks: {},
  enableOpponentIds: {},
  historyDate: new Date(),
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
    case GET_HISTORY_DATA:
      return { ...state, historyData: payload };
    case GET_UPCOMING_DATA:
      return { ...state, upcomingData: payload };
    case GET_INPLAY_DATA:
      return { ...state, inplayData: payload };
    case GET_RELATION_DATA:
      return { ...state, relationData: payload };
    case GET_RELATION_SET_NUMBER:
      return { ...state, setNumber: payload };
    case GET_RELATION_BREAKS:
      return { ...state, breaks: payload };
    case GET_RELATION_ENABLE_OPPONENT_IDS:
      return { ...state, enableOpponentIds: payload };
    case GET_HISTORY_DATE:
      return { ...state, historyDate: payload };
    case GET_ACCOUNT_INFO:
      return { ...state, accountInfo: payload };
    default:
      return initialState;
  }
};

export default reducer;
