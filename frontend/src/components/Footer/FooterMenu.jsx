import React from 'react';
import { Link } from 'react-router-dom';
import PropTypes from 'prop-types';

const FooterMenu = (props) => {
  const { activeMenu, handleMenuClicked } = props;

  return (
    <div className="text-sm-left footer-link">
      <Link
        to="/about-us"
        className={activeMenu === 7 ? 'text-foot active' : 'text-foot'}
        onClick={() => handleMenuClicked(7)}
      >
        About us
      </Link>
      <Link
        to="/pricing"
        className={activeMenu === 8 ? 'text-foot active' : 'text-foot'}
        onClick={() => handleMenuClicked(8)}
      >
        Pricing
      </Link>
      <Link
        to="/contact-us"
        className={activeMenu === 9 ? 'text-foot active' : 'text-foot'}
        onClick={() => handleMenuClicked(9)}
      >
        Contact us
      </Link>
      <Link
        to="/terms-and-conditions"
        className={activeMenu === 10 ? 'text-foot active' : 'text-foot'}
        onClick={() => handleMenuClicked(10)}
      >
        Terms and Conditions
      </Link>
      <Link
        to="/privacy-policy"
        className={activeMenu === 11 ? 'text-foot active' : 'text-foot'}
        onClick={() => handleMenuClicked(11)}
      >
        Privacy policy
      </Link>
    </div>
  );
};

FooterMenu.propTypes = {
  activeMenu: PropTypes.number,
  handleMenuClicked: PropTypes.func,
};

export default FooterMenu;
