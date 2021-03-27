import React, { useState } from 'react';
import ReactDOM from 'react-dom';
import { BrowserRouter as Router, Route, Switch } from 'react-router-dom';
import ScrollUpButton from 'react-scroll-up-button';
import { createStore, combineReducers } from 'redux';
import { persistStore, persistReducer } from 'redux-persist';
import storage from 'redux-persist/lib/storage';
import { PersistGate } from 'redux-persist/integration/react';
import { Provider } from 'react-redux';
import reducer from './store/reducers';

import Header from './components/Header';
import Footer from './components/Footer';

import InPlay from './pages/InPlay';
import Upcoming from './pages/Upcoming';
import History from './pages/History';
import AccountSetting from './pages/AccountSetting';
import AboutUs from './pages/AboutUs';
import Pricing from './pages/Pricing';
import TermsAndConditions from './pages/TermsAndConditions';
import PrivacyPolicy from './pages/PrivacyPolicy';
import PageNotFound from './pages/PageNotFound';

const AppRouter = () => {
  const rootReducer = combineReducers({
    tennis: reducer,
  });
  const persistConfig = {
    key: 'root',
    storage,
  };
  const persistedReducer = persistReducer(persistConfig, rootReducer);
  const store = createStore(persistedReducer);
  const persistor = persistStore(store);
  const [activeMenu, setActiveMenu] = useState(0);

  return (
    <Provider store={store}>
      <PersistGate loading={null} persistor={persistor}>
        <Router basename="/">
          <ScrollUpButton />
          <Header activeMenu={activeMenu} setActiveMenu={setActiveMenu} />
          <Switch>
            <Route path="/" exact component={InPlay} />
            <Route
              path="/terms-and-conditions"
              exact
              component={TermsAndConditions}
            />
            <Route path="/inplay" exact component={InPlay} />
            <Route path="/upcoming" exact component={Upcoming} />
            <Route path="/history" exact component={History} />
            <Route path="/account-setting" exact component={AccountSetting} />
            <Route path="/about-us" exact component={AboutUs} />
            <Route path="/pricing" exact component={Pricing} />
            <Route path="/privacy-policy" exact component={PrivacyPolicy} />
            <Route component={PageNotFound} />
          </Switch>
          <Footer activeMenu={activeMenu} setActiveMenu={setActiveMenu} />
        </Router>
      </PersistGate>
    </Provider>
  );
};

ReactDOM.render(<AppRouter />, document.getElementById('root'));