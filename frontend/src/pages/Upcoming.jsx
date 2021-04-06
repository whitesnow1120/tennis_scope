import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet';
import { css } from '@emotion/core';
import BounceLoader from 'react-spinners/BounceLoader';

import { filterByRankOdd } from '../utils';
import { getUpcomingData } from '../apis';
import MatchItem from '../components/MatchItem';
import { SITE_SEO_TITLE, SITE_SEO_DESCRIPTION } from '../common/Constants';
import RankButtonGroup from '../components/RankButtonGroup';
import CustomSlider from '../components/CustomSlider/slider';

const Upcoming = () => {
  const [upcomingData, setUpcomingData] = useState([]);
  const [loading, setLoading] = useState(false);
  const [activeFilter, setActiveFilter] = useState(1);
  const defaultValues = [1, 2];
  const domain = [1, 2];
  const [values, setValues] = useState(defaultValues.slice());
  const override = css`
    display: block;
    margin: 0 auto;
    border-color: red;
  `;

  const handleChange = (value) => {
    setValues(value);
  };

  useEffect(() => {
    const loadUpcomingData = async () => {
      const response = await getUpcomingData();
      if (response.status === 200) {
        const filteredData = filterByRankOdd(
          response.data,
          activeFilter,
          values
        );
        setUpcomingData(filteredData);
      } else {
        setUpcomingData([]);
      }
      // Call the async function again
      setTimeout(function () {
        loadUpcomingData();
      }, 1000 * 60 * 10);
    };

    loadUpcomingData();
  }, [activeFilter, values]);

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
          <div className="row">
            <RankButtonGroup
              setActiveFilter={setActiveFilter}
              activeFilter={activeFilter}
            />
            <CustomSlider
              handleChange={handleChange}
              values={values}
              domain={domain}
              step={0.1}
            />
          </div>
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
              <></>
            )}
          </div>
        </div>
      </section>
    </>
  );
};

export default Upcoming;
