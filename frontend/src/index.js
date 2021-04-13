import React, { useState } from 'react';
import ReactDOM from 'react-dom';
import { BrowserRouter as Router, Route, Switch } from 'react-router-dom';
import ScrollUpButton from 'react-scroll-up-button';
import { applyMiddleware, createStore, combineReducers } from 'redux';
import thunk from 'redux-thunk';
import { Provider } from 'react-redux';

import reducer from './store/reducers';
import Header from './components/Header';
import Footer from './components/Footer';
import SignIn from './pages/Auth/SignIn';
import InPlay from './pages/InPlay';
import Upcoming from './pages/Upcoming';
import History from './pages/History';
import Trigger1 from './pages/Trigger1';
import Trigger2 from './pages/Trigger2';
import Robots from './pages/Robots';
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
  const store = createStore(rootReducer, applyMiddleware(thunk));
  const [activeMenu, setActiveMenu] = useState(0);
  const [filterChanged, setFilterChanged] = useState(false);
  const [inplayScoreData, setInplayScoreData] = useState([]);

  return (
    <Provider store={store}>
      <Router basename="/">
        <ScrollUpButton />
        <Header
          activeMenu={activeMenu}
          setActiveMenu={setActiveMenu}
          filterChanged={filterChanged}
          setInplayScoreData={setInplayScoreData}
        />
        <Switch>
          <Route path="/" exact component={SignIn} />
          <Route path="/inplay" exact>
            <InPlay
              filterChanged={filterChanged}
              setFilterChanged={setFilterChanged}
              inplayScoreData={inplayScoreData}
            />
          </Route>
          <Route path="/upcoming" exact>
            <Upcoming
              filterChanged={filterChanged}
              setFilterChanged={setFilterChanged}
            />
          </Route>
          <Route path="/history" exact>
            <History
              filterChanged={filterChanged}
              setFilterChanged={setFilterChanged}
            />
          </Route>
          <Route path="/trigger1" exact>
            <Trigger1
              filterChanged={filterChanged}
              setFilterChanged={setFilterChanged}
              inplayScoreData={inplayScoreData}
            />
          </Route>
          <Route path="/trigger2" exact>
            <Trigger2
              filterChanged={filterChanged}
              setFilterChanged={setFilterChanged}
              inplayScoreData={inplayScoreData}
            />
          </Route>
          <Route path="/robots" exact>
            <Robots />
          </Route>
          <Route path="/account-setting" exact component={AccountSetting} />
          <Route path="/about-us" exact component={AboutUs} />
          <Route path="/pricing" exact component={Pricing} />
          <Route path="/privacy-policy" exact component={PrivacyPolicy} />
          <Route
            path="/terms-and-conditions"
            exact
            component={TermsAndConditions}
          />
          <Route component={PageNotFound} />
        </Switch>
        <Footer activeMenu={activeMenu} setActiveMenu={setActiveMenu} />
      </Router>
    </Provider>
  );
};

ReactDOM.render(<AppRouter />, document.getElementById('root'));
