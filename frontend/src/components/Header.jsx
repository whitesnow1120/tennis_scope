import React, { useState, useEffect } from 'react';
import { Link, Redirect } from 'react-router-dom';
import { useSelector, useDispatch } from 'react-redux';
import PropTypes from 'prop-types';
import useSound from 'use-sound';

import { SLIDER_RANGE } from '../common/Constants';
import { getInplayData, getInplayScoreData } from '../apis';
import {
  filterByRankOdd,
  addInplayScores,
  filterTrigger1,
  itemNotExist,
} from '../utils';
import { GET_USER_STATUS } from '../store/actions/types';
import BrowserButtonListener from './BrowserButtonListener';
import logo from '../assets/img/logo.png';
import logout from '../assets/img/logout.png';
import ding from '../assets/ding.mp3';

const Header = (props) => {
  const {
    activeMenu,
    setActiveMenu,
    filterChanged,
    setInplayScoreData,
  } = props;
  const dispatch = useDispatch();
  const { userLoggedIn } = useSelector((state) => state.tennis);
  const loggedIn = localStorage.getItem('isLoggedIn');
  const [triggerData, setTriggerData] = useState([]);
  const [browserButtonPressed, setBrowserButtonPressed] = useState(false);

  const [newTrigger1, setNewTrigger1] = useState(false);
  const [newTrigger2, setNewTrigger2] = useState(false);
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
  const [play] = useSound(ding, { interrupt: true });
  const [played1, setPlayed1] = useState(false);
  const [played2, setPlayed2] = useState(false);

  // check trigger matches status
  useEffect(() => {
    const loadTriggerData = async () => {
      const response = await getInplayData();
      if (response.status === 200) {
        setTriggerData(response.data);
      } else {
        setTriggerData([]);
      }
      // Call the async function again
      setTimeout(function () {
        loadTriggerData();
      }, 1000 * 60 * 5);
    };

    loadTriggerData();
  }, []);

  // update matches every 4 seconds
  useEffect(() => {
    let pathName = window.location.pathname;
    const loadTriggerScoreData = async () => {
      const response = await getInplayScoreData();
      if (response.status === 200) {
        setInplayScoreData(response.data);
        const rankFilter = localStorage.getItem('rankFilter');
        const activeRank = rankFilter === null ? '1' : rankFilter;
        const sliderChanged = JSON.parse(localStorage.getItem('sliderChanged'));
        const defaultValues =
          sliderChanged === null ? SLIDER_RANGE : sliderChanged;
        const values = defaultValues.slice();

        const filteredDataByRankOdd = filterByRankOdd(
          triggerData['inplay_detail'],
          activeRank,
          values,
          1
        );
        const filteredData = addInplayScores(
          filteredDataByRankOdd,
          response.data
        );
        const data = {
          inplay_detail: filteredData,
          players_detail: triggerData['players_detail'],
        };

        // Trigger 1
        const filteredTrigger1Data = filterTrigger1(data, trigger1DataBySet, 1);
        let trigger1 = JSON.parse(localStorage.getItem('trigger1'));
        if (trigger1 === null) {
          trigger1 = {
            set1: [],
            set2: [],
            set3: [],
          };
        }
        if (
          itemNotExist(filteredTrigger1Data['set1'], trigger1['set1']) ||
          itemNotExist(filteredTrigger1Data['set2'], trigger1['set2']) ||
          itemNotExist(filteredTrigger1Data['set3'], trigger1['set3'])
        ) {
          if (!played1) {
            play();
            setPlayed1(true);
          }
          setNewTrigger1(true);
        } else {
          setNewTrigger1(false);
        }
        setTrigger1DataBySet(filteredTrigger1Data);

        // Trigger 2
        const filteredTrigger2Data = filterTrigger1(data, trigger2DataBySet, 2);
        let trigger2 = JSON.parse(localStorage.getItem('trigger2'));
        if (trigger2 === null) {
          trigger2 = {
            set1: [],
            set2: [],
            set3: [],
          };
        }
        if (
          itemNotExist(filteredTrigger2Data['set1'], trigger2['set1']) ||
          itemNotExist(filteredTrigger2Data['set2'], trigger2['set2']) ||
          itemNotExist(filteredTrigger2Data['set3'], trigger2['set3'])
        ) {
          if (!played2) {
            play();
            setPlayed2(true);
          }
          setNewTrigger2(true);
        } else {
          setNewTrigger2(false);
        }
        setTrigger2DataBySet(filteredTrigger2Data);
      }
    };
    const timer = window.setInterval(() => {
      pathName = window.location.pathname;
      if (
        !pathName.includes('/trigger') &&
        'inplay_detail' in triggerData &&
        triggerData['inplay_detail'].length > 0
      ) {
        loadTriggerScoreData();
      }
    }, 1000 * 4);

    if (
      pathName.includes('/trigger') &&
      'inplay_detail' in triggerData &&
      triggerData['inplay_detail'].length > 0
    ) {
      loadTriggerScoreData();
    }
    return () => {
      window.clearInterval(timer);
    };
  }, [triggerData, filterChanged]);

  useEffect(() => {
    const pathName = window.location.pathname;
    if (pathName.includes('/inplay')) {
      setActiveMenu(1);
    } else if (pathName.includes('/upcoming')) {
      setActiveMenu(2);
    } else if (pathName.includes('/history')) {
      setActiveMenu(3);
    } else if (pathName.includes('/trigger1')) {
      setActiveMenu(4);
    } else if (pathName.includes('/trigger2')) {
      setActiveMenu(5);
    } else if (pathName.includes('/account-setting')) {
      setActiveMenu(6);
    } else if (pathName.includes('/about-us')) {
      setActiveMenu(7);
    } else if (pathName.includes('/pricing')) {
      setActiveMenu(8);
    } else if (pathName.includes('/contact-us')) {
      setActiveMenu(9);
    } else if (pathName.includes('/terms-and-conditions')) {
      setActiveMenu(10);
    } else if (pathName.includes('/privacy-policy')) {
      setActiveMenu(11);
    } else {
      setActiveMenu(0);
    }
    setBrowserButtonPressed(false);
  }, [browserButtonPressed]);

  const handleMenuItemClicked = (menu) => {
    setActiveMenu(menu);
    if (menu === 4) {
      setNewTrigger1(false);
      setPlayed1(false);
    } else if (menu === 5) {
      setNewTrigger2(false);
      setPlayed2(false);
    }
  };

  const handleLogout = () => {
    localStorage.clear();
    dispatch({ type: GET_USER_STATUS, payload: false });
  };

  return (
    <>
      {loggedIn || userLoggedIn ? (
        <header id="topnav" className="defaultscroll sticky nav-sticky">
          <BrowserButtonListener
            setBrowserButtonPressed={setBrowserButtonPressed}
          />
          <div className="container-fluid">
            <ul className="navigation-menu float-left">
              <li>
                <Link
                  to="/"
                  className="logo"
                  onClick={() => handleMenuItemClicked(0)}
                >
                  <img src={logo} alt="logo" />
                </Link>
              </li>
              <li className="navigation-item">
                <Link
                  className={activeMenu === 1 ? ' active' : ''}
                  to={`/inplay`}
                  onClick={() => handleMenuItemClicked(1)}
                >
                  In Play
                </Link>
              </li>
              <li className="navigation-item">
                <Link
                  className={activeMenu === 2 ? 'active' : ''}
                  to={`/upcoming`}
                  onClick={() => handleMenuItemClicked(2)}
                >
                  UpComing
                </Link>
              </li>
              <li className="navigation-item">
                <Link
                  className={activeMenu === 3 ? 'active' : ''}
                  to={`/history`}
                  onClick={() => handleMenuItemClicked(3)}
                >
                  History
                </Link>
              </li>
              <li className="navigation-item">
                <Link
                  className={activeMenu === 4 ? 'active' : ''}
                  to={`/trigger1`}
                  onClick={() => handleMenuItemClicked(4)}
                >
                  Trigger1
                </Link>
                {newTrigger1 && <div className="green-dot"></div>}
              </li>
              <li className="navigation-item">
                <Link
                  className={activeMenu === 5 ? 'active' : ''}
                  to={`/trigger2`}
                  onClick={() => handleMenuItemClicked(5)}
                >
                  Trigger2
                </Link>
                {newTrigger2 && <div className="green-dot"></div>}
              </li>
            </ul>
            <div id="account">
              <ul className="navigation-menu float-right">
                <li className="navigation-item">
                  <Link
                    className={activeMenu === 6 ? 'active' : ''}
                    to={`/account-setting`}
                    onClick={() => handleMenuItemClicked(6)}
                  >
                    Andrejs K.
                  </Link>
                </li>
                <li className="navigation-item account-type">
                  <a href="/">
                    <i className="far fa-star pr-1"></i>Premium Account
                  </a>
                </li>
                <span onClick={() => handleLogout()}>
                  <img src={logout} alt="logout" />
                </span>
              </ul>
            </div>
          </div>
        </header>
      ) : (
        <Redirect to="/" />
      )}
    </>
  );
};

Header.propTypes = {
  activeMenu: PropTypes.number,
  setActiveMenu: PropTypes.func,
  filterChanged: PropTypes.bool,
  setInplayScoreData: PropTypes.func,
};

export default Header;
