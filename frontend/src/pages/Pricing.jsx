import React from 'react';

import Hero from '../components/Hero';
import paymentLogos from '../assets/img/payment_logos.png';

const Pricing = () => {
  return (
    <>
      <Hero title="Pricing" />
      <div className="container pb-5 description">
        <div className="row justify-content-center">
          <div className="col-12 text-center">
            <h1>Tenniscope pricing</h1>
          </div>
          <div className="col-12 mt-4 pay-with">
            <p>
              Analyze player performance from multiple angles and make logical
              decisions in tennis betting. You can save tons of effort and money
              by purchasing our product. However part of it will always remain
              free for anybody’s use.
            </p>
            <p>Pay with:</p>
          </div>
          <div className="col-12 text-center mt-2 payment-logos-pic">
            <img src={paymentLogos} alt='payment logos' />
          </div>
        </div>
      </div>
    </>
  );
};

export default Pricing;
