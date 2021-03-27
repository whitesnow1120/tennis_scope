import React from 'react';
import { Link } from 'react-router-dom';
import { Helmet } from 'react-helmet';

import { SITE_NAME } from '../common/Constants';

const PageNotFound = () => {
  return (
    <>
      <Helmet>
        <title>Page Not Found - {SITE_NAME}</title>
        <meta property="og:title" content={`Page Not Found - ${SITE_NAME}`} />
      </Helmet>
      <div className="container mt-5 mb-5 pt-5 pb-5">
        <div className="row justify-content-center mt-5 mb-5">
          <div className="col-lg-8 col-md-12 text-center mt-5 mb-5">
            <div className="text-uppercase mt-4 display-3">Oh! no</div>
            <div className="text-capitalize text-dark mb-4 error-page">
              Page Not Found
            </div>
            <p className="text-muted para-desc mx-auto">
              Sorry, we couldn’t find the page you were looking for.
            </p>
          </div>
        </div>

        <div className="row">
          <div className="col-md-12 text-center">
            <Link to="/" className="btn btn-primary mt-4 ml-2">
              Go To Home
            </Link>
          </div>
        </div>
      </div>
    </>
  );
};

export default PageNotFound;
