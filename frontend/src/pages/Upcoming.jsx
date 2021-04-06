import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet';
import { css } from '@emotion/core';
import BounceLoader from 'react-spinners/BounceLoader';

import { filterByRankOdd } from '../utils';
import { getUpcomingData } from '../apis';
import MatchItem from '../components/MatchItem';
import {
  SITE_SEO_TITLE,
  SITE_SEO_DESCRIPTION,
  SLIDER_RANGE,
  SLIDER_STEP,
} from '../common/Constants';
import RankButtonGroup from '../components/RankButtonGroup';
import CustomSlider from '../components/CustomSlider/slider';

const Upcoming = () => {
  const [upcomingData, setUpcomingData] = useState([]);
  const [upcomingFilteredData, setUpcomingFilteredData] = useState([]);
  const [loading, setLoading] = useState(false);
  const rankFilter = localStorage.getItem('rankFilter');
  const [activeRank, setActiveRank] = useState(
    rankFilter === null ? '1' : rankFilter
  );

  const sliderChanged = JSON.parse(localStorage.getItem('sliderChanged'));
  const [sliderValue, setSliderValue] = useState(
    sliderChanged === null ? '0' : '1'
  );
  const defaultValues = sliderChanged === null ? SLIDER_RANGE : sliderChanged;
  const domain = SLIDER_RANGE;
  const [values, setValues] = useState(defaultValues.slice());
  const override = css`
    display: block;
    margin: 0 auto;
    border-color: red;
  `;

  const handleSliderChange = (value) => {
    setValues(value);
    setSliderValue(sliderValue === '0' ? '1' : '0');
    localStorage.setItem('sliderChanged', JSON.stringify(value));
  };

  const handleSliderUpdate = (value) => {
    setValues(value);
  };

  useEffect(() => {
    const loadUpcomingData = async () => {
      const response = await getUpcomingData();
      if (response.status === 200) {
        setUpcomingData(response.data);
        const filteredData = filterByRankOdd(response.data, activeRank, values);
        setUpcomingFilteredData(filteredData);
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

  useEffect(() => {
    const filteredData = filterByRankOdd(upcomingData, activeRank, values);
    setUpcomingFilteredData(filteredData);
  }, [activeRank, sliderValue]);

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
              activeRank={activeRank}
              setActiveRank={setActiveRank}
            />
            <CustomSlider
              handleChange={handleSliderChange}
              handleUpdate={handleSliderUpdate}
              values={values}
              domain={domain}
              step={SLIDER_STEP}
            />
          </div>
          <div className="row mt-4">
            {upcomingFilteredData.length > 0 ? (
              upcomingFilteredData.map((item) => (
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
