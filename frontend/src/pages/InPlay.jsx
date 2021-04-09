import React, { useState, useEffect } from 'react';
import { useDispatch } from 'react-redux';
import { Helmet } from 'react-helmet';
import { css } from '@emotion/core';
import BounceLoader from 'react-spinners/BounceLoader';
import PropTypes from 'prop-types';

import { GET_OPENED_DETAIL } from '../store/actions/types';
import { filterByRankOdd, addInplayScores } from '../utils';
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

const Inplay = (props) => {
  const { filterChanged, setFilterChanged, inplayScoreData } = props;
  const dispatch = useDispatch();
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
    dispatch({
      type: GET_OPENED_DETAIL,
      payload: {},
    });
    setValues(value);
    setSliderValue(sliderValue === '0' ? '1' : '0');
    localStorage.setItem('sliderChanged', JSON.stringify(value));
  };

  // update matches every 5 minutes
  useEffect(() => {
    const loadInplayData = async () => {
      const response = await getInplayData();
      if (response.status === 200) {
        let activedRank = localStorage.getItem('rankFilter');
        activedRank = activedRank === null ? '1' : activedRank;
        let sliderValues = JSON.parse(localStorage.getItem('sliderChanged'));
        sliderValues = sliderValues === null ? SLIDER_RANGE : sliderValues;
        const data = response.data.inplay_detail;
        const filteredData = filterByRankOdd(
          data,
          activedRank,
          sliderValues,
          1
        );
        setInplayData(data);
        setInplayFilteredData(filteredData);
      } else {
        setInplayData([]);
      }
      // Call the async function again
      setTimeout(function () {
        const pathName = window.location.pathname;
        if (pathName.includes('/trigger')) {
          loadInplayData();
        }
      }, 1000 * 60 * 5); // update every 5 minutes
    };

    loadInplayData();
  }, []);

  // update matches every 4 seconds
  useEffect(() => {
    setFilterChanged(!filterChanged);
    let pathName = window.location.pathname;
    const loadInplayScoreData = async () => {
      const filteredDataByRankOdd = filterByRankOdd(
        inplayData,
        activeRank,
        values,
        1
      );
      const filteredData = addInplayScores(
        filteredDataByRankOdd,
        inplayScoreData
      );
      setInplayFilteredData(filteredData);
    };

    if (pathName.includes('/inplay') && inplayData.length > 0) {
      loadInplayScoreData();
    }
  }, [inplayData, activeRank, sliderValue, inplayScoreData]);

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
              values={values}
              domain={domain}
              step={SLIDER_STEP}
            />
          </div>
          <div className="row mt-4">
            {inplayFilteredData.length > 0 ? (
              inplayFilteredData.map((item, index) => (
                <MatchItem
                  key={index}
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

Inplay.propTypes = {
  filterChanged: PropTypes.bool,
  setFilterChanged: PropTypes.func,
  inplayScoreData: PropTypes.array,
};

export default Inplay;
