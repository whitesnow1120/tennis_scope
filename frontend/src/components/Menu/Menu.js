import React from 'react';
import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';
import { StyledMenu } from './Menu.styled';

import logoFooterIcon from '../../assets/img/logo_footer.png';
import logoIcon from '../../assets/img/logo.png';

import MobileFooter from '../Footer/MobileFooter';

const Menu = ({
  open,
  setOpen,
  children,
  activeMenu,
  setActiveMenu,
  handleLogout,
  ...props
}) => {
  const isHidden = open ? true : false;
  // const tabIndex = isHidden ? 0 : -1;

  const handleMenuItemClicked = (menu) => {
    setOpen(false);
    setActiveMenu(menu);
  };

  return (
    <StyledMenu
      open={open}
      aria-hidden={!isHidden}
      {...props}
      className="footer"
    >
      <div className="mobile-navigation">
        <div className="mobile-menu-back">
          <i className="fal fa-arrow-left"></i>
        </div>
        <div className="mobile-logo">
          <Link to="/" className="logo">
            <img src={logoIcon} alt="logo" />
            <img src={logoFooterIcon} alt="logo" className="pl-2" />
          </Link>
        </div>
        <div className="mobile-logout" onClick={handleLogout}>
          <span>
            <i className="fal fa-sign-out"></i>
          </span>
        </div>
      </div>
      <div className="mobile-menu">{children}</div>
      <div className="mobile-user-info">
        <div className="mobile-user-name">
          <Link
            className={activeMenu === 6 ? 'active' : ''}
            to={`/account-setting`}
            onClick={() => handleMenuItemClicked(6)}
          >
            Andrejs K.
          </Link>
        </div>
        <div className="mobile-user-account">
          <a href="/">
            <i className="far fa-star pr-1"></i>Premium
          </a>
        </div>
      </div>
      <MobileFooter
        activeMenu={activeMenu}
        setActiveMenu={setActiveMenu}
        setOpen={setOpen}
      />
    </StyledMenu>
  );
};

Menu.propTypes = {
  open: PropTypes.bool,
  children: PropTypes.any,
  activeMenu: PropTypes.number,
  setActiveMenu: PropTypes.func,
  setOpen: PropTypes.func,
  handleLogout: PropTypes.func,
};

export default Menu;
