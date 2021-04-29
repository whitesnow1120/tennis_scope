import React from 'react';
import PropTypes from 'prop-types';
import { StyledBurger } from './Burger.styled';

const Burger = ({
  open,
  activeMenu,
  mobileMatchClicked,
  setMobileMatchClicked,
  setOpen,
  ...props
}) => {
  const isExpanded = open ? true : false;
  let activeMenuText = 'IN PLAY';
  switch (activeMenu) {
    case 1:
      activeMenuText = 'IN PLAY';
      break;
    case 2:
      activeMenuText = 'UPCOMING';
      break;
    case 3:
      activeMenuText = 'HISTORY';
      break;
    case 4:
      activeMenuText = 'TRIGGER1';
      break;
    case 5:
      activeMenuText = 'TRIGGER2';
      break;
    case 20:
      activeMenuText = 'ROBOTS';
      break;
    case 6:
      activeMenuText = 'Account setting';
      break;
    case 7:
      activeMenuText = 'About us';
      break;
    case 8:
      activeMenuText = 'Pricing';
      break;
    case 9:
      activeMenuText = 'Contact us';
      break;
    case 10:
      activeMenuText = 'Terms and conditions';
      break;
    case 11:
      activeMenuText = 'Privacy and policy';
      break;
    default:
      activeMenuText = 'IN PLAY';
      break;
  }

  const handleBackClicked = () => {
    setMobileMatchClicked(false);
  };

  return (
    <div className="mobile-hamburger-menu">
      {!mobileMatchClicked ? (
        <StyledBurger
          className="burger-menu"
          aria-label="Toggle menu"
          aria-expanded={isExpanded}
          open={open}
          onClick={() => setOpen(!open)}
          {...props}
        >
          <span />
          <span />
          <span />
          <span />
        </StyledBurger>
      ) : (
        <div onClick={handleBackClicked} className="mobile-match-back">
          <i className="fal fa-arrow-left"></i>
        </div>
      )}
      <div className="mobile-header-group">
        <div className="mobile-header-menu">
          <span>{activeMenuText}</span>
        </div>
        <div className="mobile-header-account">
          <a href="/">
            <i className="far fa-star pr-1"></i>Premium
          </a>
        </div>
      </div>
    </div>
  );
};

Burger.propTypes = {
  open: PropTypes.bool,
  setOpen: PropTypes.func,
  activeMenu: PropTypes.number,
  mobileMatchClicked: PropTypes.bool,
  setMobileMatchClicked: PropTypes.func,
};

export default Burger;
