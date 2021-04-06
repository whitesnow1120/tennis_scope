import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet';
import { css } from '@emotion/core';
import BounceLoader from 'react-spinners/BounceLoader';

import { filterByRankOdd } from '../utils';
import { getInplayData } from '../apis';
import MatchItem from '../components/MatchItem';
import {
  SITE_SEO_TITLE,
  SITE_SEO_DESCRIPTION,
  SLIDER_RANGE,
  SLIDER_STEP,
} from '../common/Constants';
import RankButtonGroup from '../components/RankButtonGroup';
import CustomSlider from '../components/CustomSlider/slider';

const Inplay = () => {
  const rankFilter = localStorage.getItem('rankFilter');
  const [activeRank, setActiveRank] = useState(
    rankFilter === null ? '1' : rankFilter
  );
  const [inplayData, setInplayData] = useState([]);
  const [inplayFilteredData, setInplayFilteredData] = useState([]);
  const [loading, setLoading] = useState(false);

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
    const loadInplayData = async () => {
      const response = await getInplayData();
      if (response.status === 200) {
        setInplayData(response.data);
        const filteredData = filterByRankOdd(response.data, activeRank, values);
        setInplayFilteredData(filteredData);
      } else {
        setInplayData([]);
      }
      // Call the async function again
      setTimeout(function () {
        loadInplayData();
      }, 1000 * 60 * 10);
    };

    loadInplayData();
  }, []);

  useEffect(() => {
    const filteredData = filterByRankOdd(inplayData, activeRank, values);
    setInplayFilteredData(filteredData);
  }, [activeRank, sliderValue]);

  return (
    <>
      <Helmet>
        <title>{SITE_SEO_TITLE} : Inplay</title>
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
      <section className="section inplay">
        <div className="container-fluid">
          <div className="row header-filter-group">
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
            {inplayFilteredData.length > 0 ? (
              inplayFilteredData.map((item) => (
                <MatchItem
                  key={item.id}
                  item={item}
                  type="inplay"
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

export default Inplay;
