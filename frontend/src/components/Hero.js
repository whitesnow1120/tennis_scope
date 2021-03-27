import React from 'react';
import PropTypes from 'prop-types';

const Hero = ({ title = 'Hello' }) => {
  return (
    <section className="bg-half bg-light d-table w-100">
      <div className="container">
        <div className="row justify-content-center">
          <div className="hero-title">
            <div className="page-next-level text-center">
              <div>
                <p className="title">{title}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
};

Hero.propTypes = {
  title: PropTypes.string,
};

export default Hero;
