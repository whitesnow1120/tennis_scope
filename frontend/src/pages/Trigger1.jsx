import React, { useState, useEffect } from 'react';
import { useDispatch } from 'react-redux';
import { Helmet } from 'react-helmet';
import { css } from '@emotion/core';
import BounceLoader from 'react-spinners/BounceLoader';
import PropTypes from 'prop-types';

import { GET_OPENED_DETAIL } from '../store/actions/types';
import { filterByRankOdd, addInplayScores, filterTrigger1 } from '../utils';
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

const Trigger1 = (props) => {
  const { inplayScoreData } = props;
  const dispatch = useDispatch();
  const rankFilter = localStorage.getItem('rankFilter');
  const [activeRank, setActiveRank] = useState(
    rankFilter === null ? '1' : rankFilter
  );
  const [trigger1Data, setTrigger1Data] = useState({
    inplay_detail: [],
    players_detail: [],
  });
  const [trigger1DataBySet, setTrigger1DataBySet] = useState({
    set1: [],
    set2: [],
    set3: [],
  });
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

  useEffect(() => {
    const loadTrigger1Data = async () => {
      const response = await getInplayData();
      if (response.status === 200) {
        setTrigger1Data(response.data);
      } else {
        setTrigger1Data([]);
      }
      // Call the async function again
      setTimeout(function () {
        loadTrigger1Data();
      }, 1000 * 60 * 5);
    };

    loadTrigger1Data();
  }, []);

  // update matches every 4 seconds
  useEffect(() => {
    const pathName = window.location.pathname;
    const loadTrigger1ScoreData = async () => {
      const filteredDataByRankOdd = filterByRankOdd(
        trigger1Data['inplay_detail'],
        activeRank,
        values,
        1
      );
      const filteredData = addInplayScores(
        filteredDataByRankOdd,
        inplayScoreData
      );
      const data = {
        inplay_detail: filteredData,
        players_detail: trigger1Data['players_detail'],
      };
      const filteredTrigger1Data = filterTrigger1(data, trigger1DataBySet, 1);
      let clickedEvents = JSON.parse(
        localStorage.getItem('clickedEventsTrigger1')
      );
      if (clickedEvents === null) {
        clickedEvents = {
          set1: [],
          set2: [],
          set3: [],
        };
      }
      if (filteredTrigger1Data['set1'].length === 0) {
        clickedEvents['set1'] = [];
      }
      if (filteredTrigger1Data['set2'].length === 0) {
        clickedEvents['set2'] = [];
      }
      if (filteredTrigger1Data['set3'].length === 0) {
        clickedEvents['set3'] = [];
      }
      localStorage.setItem(
        'clickedEventsTrigger1',
        JSON.stringify(clickedEvents)
      );
      const set1Ids = filteredTrigger1Data['set1'].map((f) => {
        return f['event_id'];
      });
      const set2Ids = filteredTrigger1Data['set2'].map((f) => {
        return f['event_id'];
      });
      const set3Ids = filteredTrigger1Data['set3'].map((f) => {
        return f['event_id'];
      });
      const eventIds = {
        set1: set1Ids,
        set2: set2Ids,
        set3: set3Ids,
      };
      localStorage.setItem('trigger1', JSON.stringify(eventIds));
      setTrigger1DataBySet(filteredTrigger1Data);
    };

    if (
      pathName.includes('/trigger1') &&
      'inplay_detail' in trigger1Data &&
      trigger1Data['inplay_detail'].length > 0
    ) {
      loadTrigger1ScoreData();
    }
  }, [trigger1Data, activeRank, sliderValue, inplayScoreData]);

  return (
    <>
      <Helmet>
        <title>{SITE_SEO_TITLE} : Trigger1</title>
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
      <section className="section trigger">
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
            <div className="col-12 trigger1">
              <div className="trigger1-set">
                <span>1st SET TRIGGER</span>
              </div>
              <div className="trigger-border"></div>
            </div>
            {trigger1DataBySet['set1'].length > 0 ? (
              trigger1DataBySet['set1'].map((item) => (
                <MatchItem
                  key={item.id}
                  item={item}
                  type="trigger1"
                  triggerSet={1}
                  loading={loading}
                  setLoading={setLoading}
                />
              ))
            ) : (
              <></>
            )}
          </div>
          <div className="row mt-4">
            <div className="col-12 trigger1">
              <div className="trigger1-set">
                <span>2nd SET TRIGGER</span>
              </div>
              <div className="trigger-border"></div>
            </div>
            {trigger1DataBySet['set2'].length > 0 ? (
              trigger1DataBySet['set2'].map((item) => (
                <MatchItem
                  key={item.id}
                  item={item}
                  type="trigger1"
                  triggerSet={2}
                  loading={loading}
                  setLoading={setLoading}
                />
              ))
            ) : (
              <></>
            )}
          </div>
          <div className="row mt-4">
            <div className="col-12 trigger1">
              <div className="trigger1-set">
                <span>3rd SET TRIGGER</span>
              </div>
              <div className="trigger-border"></div>
            </div>
            {trigger1DataBySet['set3'].length > 0 ? (
              trigger1DataBySet['set3'].map((item) => (
                <MatchItem
                  key={item.id}
                  item={item}
                  type="trigger1"
                  triggerSet={3}
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

Trigger1.propTypes = {
  inplayScoreData: PropTypes.array,
};

export default Trigger1;
