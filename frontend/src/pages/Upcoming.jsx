import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet';
import { css } from '@emotion/core';
import BounceLoader from 'react-spinners/BounceLoader';

import MatchItem from '../components/MatchItem';
import { getUpcomingData } from '../apis';
import { SITE_SEO_TITLE, SITE_SEO_DESCRIPTION } from '../common/Constants';

const Upcoming = () => {
  const [upcomingData, setUpcomingData] = useState([]);
  const [loading, setLoading] = useState(false);
  const override = css`
    display: block;
    margin: 0 auto;
    border-color: red;
  `;

  useEffect(() => {
    const loadUpcomingData = async () => {
      const response = await getUpcomingData();
      if (response.status === 200) {
        setUpcomingData(response.data);
      } else {
        setUpcomingData([]);
      }
      // Call the async function again
      setTimeout(function () {
        loadUpcomingData();
      }, 1000 * 60 * 10);
    };

    loadUpcomingData();
  }, []);

  return (
    <>
      <Helmet>
        <title>{SITE_SEO_TITLE} : Upcoming</title>
        <meta property="og:title" content={SITE_SEO_TITLE} />
        <meta name="description" content={SITE_SEO_DESCRIPTION} />
        <meta property="og:description" content={SITE_SEO_DESCRIPTION} />
      </Helmet>
      {loading && (
        <div className="loading">
          <div className="loader">
            <BounceLoader loading={loading} css={override} size={100} />
          </div>
        </div>
      )}
      <section className="section upcoming">
        <div className="container-fluid">
          <div className="row mt-4">
            {upcomingData.length > 0 ? (
              upcomingData.map((item) => (
                <MatchItem
                  key={item.id}
                  item={item}
                  type="upcoming"
                  loading={loading}
                  setLoading={setLoading}
                />
              ))
            ) : (
              // <div className="no-result col-12">
              //   <span>There is no upcoming data</span>
              // </div>
              <></>
            )}
          </div>
        </div>
      </section>
    </>
  );
};

export default Upcoming;
