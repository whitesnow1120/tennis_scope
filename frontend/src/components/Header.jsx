import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';

import BrowserButtonListener from './BrowserButtonListener';
import logo from '../assets/img/logo.png';
import logout from '../assets/img/logout.png';

const Header = (props) => {
  const { activeMenu, setActiveMenu } = props;
  const [browserButtonPressed, setBrowserButtonPressed] = useState(false);

  useEffect(() => {
    const pathName = window.location.pathname;
    if (pathName.includes('/inplay')) {
      setActiveMenu(1);
    } else if (pathName.includes('/upcoming')) {
      setActiveMenu(2);
    } else if (pathName.includes('/history')) {
      setActiveMenu(3);
    } else if (pathName.includes('/account-setting')) {
      setActiveMenu(4);
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
  };

  const handleLogout = () => {};

  return (
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
        </ul>
        <div id="account">
          <ul className="navigation-menu float-right">
            <li className="navigation-item">
              <Link
                className={activeMenu === 4 ? 'active' : ''}
                to={`/account-setting`}
                onClick={() => handleMenuItemClicked(4)}
              >
                Andrejs K.
              </Link>
            </li>
            <li className='navigation-item account-type'>
              <a href='/'><i className="far fa-star pr-1"></i>Premium Account</a>
            </li>
            <span onClick={() => handleLogout()}>
              <img src={logout} alt="logout" />
            </span>
          </ul>
        </div>
      </div>
    </header>
  );
};

Header.propTypes = {
  activeMenu: PropTypes.number,
  setActiveMenu: PropTypes.func,
};

export default Header;
