import React, { useState, useEffect, useRef } from 'react';
import ReactDOM from 'react-dom';
import { BrowserRouter as Router, Route, Switch } from 'react-router-dom';
import ScrollUpButton from 'react-scroll-up-button';
import { applyMiddleware, createStore, combineReducers } from 'redux';
import thunk from 'redux-thunk';
import { Provider } from 'react-redux';
import FocusLock from 'react-focus-lock';
import { ThemeProvider } from 'styled-components';
import './assets/css/style.scss';

import { useOnClickOutside } from './hooks';
import { theme } from './theme';

import reducer from './store/reducers';
import Header from './components/Header';
import Footer from './components/Footer';
import Burger from './components/Burger';
import Menu from './components/Menu';
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

  /** menu */
  const [open, setOpen] = useState(false);
  const node = useRef();
  const menuId = 'main-menu';

  useOnClickOutside(node, () => setOpen(false));

  const [activeMenu, setActiveMenu] = useState(0);
  const [filterChanged, setFilterChanged] = useState(false);
  const [inplayScoreData, setInplayScoreData] = useState([]);
  const [trigger1DataBySet, setTrigger1DataBySet] = useState({
    set1: [],
    set2: [],
    set3: [],
  });
  const [trigger2DataBySet, setTrigger2DataBySet] = useState({
    set1: [],
    set2: [],
    set3: [],
  });
  const [roboPicks, setRoboPicks] = useState(false);
  const [performanceToday, setPerformanceToday] = useState();

  const loggedIn = localStorage.getItem('isLoggedIn');
  const [loginStatus, setLoginStatus] = useState(loggedIn);
  const [mobileMatchClicked, setMobileMatchClicked] = useState(false);
  // check device
  const [mobileView, setMobileView] = useState(
    window.innerWidth < 768 ? true : false
  );

  const handleWindowSizeChange = () => {
    if (window.innerWidth < 768) {
      setMobileView(true);
    } else {
      setMobileView(false);
    }
  };

  const handleLogout = () => {
    localStorage.clear();
    setLoginStatus(false);
  };

  useEffect(() => {
    window.addEventListener('resize', handleWindowSizeChange);
    return () => {
      window.removeEventListener('resize', handleWindowSizeChange);
    };
  }, []);

  useEffect(() => {}, []);

  return (
    <ThemeProvider theme={theme}>
      <Provider store={store}>
        <Router basename="/">
          <ScrollUpButton />
          {loginStatus && (
            <div ref={node} className="mobile-top-menu">
              <FocusLock disabled={!open}>
                <Burger
                  open={open}
                  setOpen={setOpen}
                  aria-controls={menuId}
                  activeMenu={activeMenu}
                  mobileMatchClicked={mobileMatchClicked}
                  setMobileMatchClicked={setMobileMatchClicked}
                />
                <Menu
                  open={open}
                  setOpen={setOpen}
                  id={menuId}
                  handleLogout={handleLogout}
                  activeMenu={activeMenu}
                  setActiveMenu={setActiveMenu}
                >
                  {mobileView && (
                    <Header
                      setOpen={setOpen}
                      activeMenu={activeMenu}
                      setActiveMenu={setActiveMenu}
                      filterChanged={filterChanged}
                      setInplayScoreData={setInplayScoreData}
                      trigger1DataBySet={trigger1DataBySet}
                      setTrigger1DataBySet={setTrigger1DataBySet}
                      trigger2DataBySet={trigger2DataBySet}
                      setTrigger2DataBySet={setTrigger2DataBySet}
                      setPerformanceToday={setPerformanceToday}
                      handleLogout={handleLogout}
                    />
                  )}
                </Menu>
              </FocusLock>
            </div>
          )}
          {!mobileView && (
            <Header
              setOpen={setOpen}
              activeMenu={activeMenu}
              setActiveMenu={setActiveMenu}
              filterChanged={filterChanged}
              setInplayScoreData={setInplayScoreData}
              trigger1DataBySet={trigger1DataBySet}
              setTrigger1DataBySet={setTrigger1DataBySet}
              trigger2DataBySet={trigger2DataBySet}
              setTrigger2DataBySet={setTrigger2DataBySet}
              setPerformanceToday={setPerformanceToday}
              handleLogout={handleLogout}
            />
          )}
          <Switch>
            <Route path="/" exact>
              <SignIn setLoginStatus={setLoginStatus} />
            </Route>
            <Route path="/inplay" exact>
              <InPlay
                filterChanged={filterChanged}
                setFilterChanged={setFilterChanged}
                inplayScoreData={inplayScoreData}
                roboPicks={roboPicks}
                setRoboPicks={setRoboPicks}
                performanceToday={performanceToday}
                mobileMatchClicked={mobileMatchClicked}
                setMobileMatchClicked={setMobileMatchClicked}
              />
            </Route>
            <Route path="/upcoming" exact>
              <Upcoming
                filterChanged={filterChanged}
                setFilterChanged={setFilterChanged}
                roboPicks={roboPicks}
                setRoboPicks={setRoboPicks}
                performanceToday={performanceToday}
                mobileMatchClicked={mobileMatchClicked}
                setMobileMatchClicked={setMobileMatchClicked}
              />
            </Route>
            <Route path="/history" exact>
              <History
                filterChanged={filterChanged}
                setFilterChanged={setFilterChanged}
                roboPicks={roboPicks}
                setRoboPicks={setRoboPicks}
                mobileMatchClicked={mobileMatchClicked}
                setMobileMatchClicked={setMobileMatchClicked}
              />
            </Route>
            <Route path="/trigger1" exact>
              <Trigger1
                filterChanged={filterChanged}
                setFilterChanged={setFilterChanged}
                inplayScoreData={inplayScoreData}
                trigger1DataBySet={trigger1DataBySet}
                mobileMatchClicked={mobileMatchClicked}
                setMobileMatchClicked={setMobileMatchClicked}
              />
            </Route>
            <Route path="/trigger2" exact>
              <Trigger2
                filterChanged={filterChanged}
                setFilterChanged={setFilterChanged}
                inplayScoreData={inplayScoreData}
                trigger2DataBySet={trigger2DataBySet}
                mobileMatchClicked={mobileMatchClicked}
                setMobileMatchClicked={setMobileMatchClicked}
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
          {!mobileMatchClicked && (
            <Footer activeMenu={activeMenu} setActiveMenu={setActiveMenu} />
          )}
        </Router>
      </Provider>
    </ThemeProvider>
  );
};

ReactDOM.render(<AppRouter />, document.getElementById('root'));
