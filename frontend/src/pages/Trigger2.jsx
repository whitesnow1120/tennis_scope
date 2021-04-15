import React, { useState, useEffect } from 'react';
// import { useDispatch } from 'react-redux';
import { Helmet } from 'react-helmet';
import { css } from '@emotion/core';
import BounceLoader from 'react-spinners/BounceLoader';
import PropTypes from 'prop-types';

// import { GET_OPENED_DETAIL } from '../store/actions/types';
import {
  filterByRankOdd,
  addInplayScores,
  filterTrigger1,
  openedDetailExistInNewMathes,
} from '../utils';
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

const Trigger2 = (props) => {
  const {
    inplayScoreData,
    filterChanged,
    setFilterChanged,
    trigger2DataBySet,
  } = props;
  // const dispatch = useDispatch();
  const rankFilter = localStorage.getItem('rankFilter');
  const [activeRank, setActiveRank] = useState(
    rankFilter === null ? '1' : rankFilter
  );
  const [trigger2Data, setTrigger2Data] = useState({
    inplay_detail: [],
    players_detail: [],
  });

  // filtered by rank and odd
  const [trigger2FilteredDataBySet, setTrigger2FilteredDataBySet] = useState({
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
  const [openedDetail, setOpenedDetail] = useState({
    p1_id: '',
    p2_id: '',
  });

  const override = css`
    display: block;
    margin: 0 auto;
    border-color: red;
  `;

  const handleSliderChange = (value) => {
    setOpenedDetail({
      p1_id: '',
      p2_id: '',
    });
    setValues(value);
    setSliderValue(sliderValue === '0' ? '1' : '0');
    localStorage.setItem('sliderChanged', JSON.stringify(value));
  };

  useEffect(() => {
    const loadTrigger2Data = async () => {
      const response = await getInplayData();
      if (response.status === 200) {
        setTrigger2Data(response.data);
      } else {
        setTrigger2Data([]);
      }
      // Call the async function again
      setTimeout(function () {
        const pathName = window.location.pathname;
        if (pathName.includes('trigger2')) {
          loadTrigger2Data();
        }
      }, 1000 * 60 * 5);
    };

    loadTrigger2Data();
  }, []);

  // update matches every 4 seconds
  useEffect(() => {
    const pathName = window.location.pathname;
    const loadTrigger2ScoreData = async () => {
      const filteredDataByRankOdd = filterByRankOdd(
        trigger2Data['inplay_detail'],
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
        players_detail: trigger2Data['players_detail'],
      };
      const filteredTrigger2Data = filterTrigger1(data, trigger2DataBySet, 2);
      let clickedEvents = JSON.parse(
        localStorage.getItem('clickedEventsTrigger2')
      );
      if (clickedEvents === null) {
        clickedEvents = {
          set1: [],
          set2: [],
          set3: [],
        };
      }
      if (filteredTrigger2Data['set1'].length === 0) {
        clickedEvents['set1'] = [];
      }
      if (filteredTrigger2Data['set2'].length === 0) {
        clickedEvents['set2'] = [];
      }
      if (filteredTrigger2Data['set3'].length === 0) {
        clickedEvents['set3'] = [];
      }
      localStorage.setItem(
        'clickedEventsTrigger2',
        JSON.stringify(clickedEvents)
      );

      /* --- store current trigger event ids in localstorage --- start --- */
      const set1Ids = filteredTrigger2Data['set1'].map((f) => {
        return f['event_id'];
      });
      const set2Ids = filteredTrigger2Data['set2'].map((f) => {
        return f['event_id'];
      });
      const set3Ids = filteredTrigger2Data['set3'].map((f) => {
        return f['event_id'];
      });
      const eventIds = {
        set1: set1Ids,
        set2: set2Ids,
        set3: set3Ids,
      };
      localStorage.setItem('trigger2', JSON.stringify(eventIds));
      /* --- store current trigger event ids in localstorage --- end --- */

      /* --- set filtered trigger data by Rankd and odd --- start --- */
      let filteredTriggerByRankOdd = {
        set1: [],
        set2: [],
        set3: [],
      };
      filteredTriggerByRankOdd['set1'] = filterByRankOdd(
        filteredTrigger2Data['set1'],
        activeRank,
        values
      );
      filteredTriggerByRankOdd['set2'] = filterByRankOdd(
        filteredTrigger2Data['set2'],
        activeRank,
        values
      );
      filteredTriggerByRankOdd['set3'] = filterByRankOdd(
        filteredTrigger2Data['set3'],
        activeRank,
        values
      );
      if (
        !openedDetailExistInNewMathes(
          filteredTriggerByRankOdd['set1'],
          openedDetail
        ) &&
        !openedDetailExistInNewMathes(
          filteredTriggerByRankOdd['set2'],
          openedDetail
        ) &&
        !openedDetailExistInNewMathes(
          filteredTriggerByRankOdd['set3'],
          openedDetail
        )
      ) {
        setOpenedDetail({
          p1_id: '',
          p2_id: '',
        });
      }
      setTrigger2FilteredDataBySet(filteredTriggerByRankOdd);
      /* --- set filtered trigger data by Rankd and odd --- end --- */
    };

    if (
      pathName.includes('/trigger2') &&
      'inplay_detail' in trigger2Data &&
      trigger2Data['inplay_detail'].length > 0
    ) {
      loadTrigger2ScoreData();
    }
  }, [trigger2Data, activeRank, sliderValue, inplayScoreData]);

  useEffect(() => {
    setFilterChanged(!filterChanged);
  }, [activeRank, sliderValue]);

  return (
    <>
      <Helmet>
        <title>{SITE_SEO_TITLE} : Trigger2</title>
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
            {trigger2FilteredDataBySet['set1'].length > 0 ? (
              trigger2FilteredDataBySet['set1'].map((item) => (
                <MatchItem
                  key={item.id}
                  item={item}
                  type="trigger1"
                  triggerSet={1}
                  loading={loading}
                  setLoading={setLoading}
                  openedDetail={openedDetail}
                  setOpenedDetail={setOpenedDetail}
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
            {trigger2FilteredDataBySet['set2'].length > 0 ? (
              trigger2FilteredDataBySet['set2'].map((item) => (
                <MatchItem
                  key={item.id}
                  item={item}
                  type="trigger2"
                  triggerSet={2}
                  loading={loading}
                  setLoading={setLoading}
                  openedDetail={openedDetail}
                  setOpenedDetail={setOpenedDetail}
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
            {trigger2FilteredDataBySet['set3'].length > 0 ? (
              trigger2FilteredDataBySet['set3'].map((item) => (
                <MatchItem
                  key={item.id}
                  item={item}
                  type="trigger2"
                  triggerSet={3}
                  loading={loading}
                  setLoading={setLoading}
                  openedDetail={openedDetail}
                  setOpenedDetail={setOpenedDetail}
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

Trigger2.propTypes = {
  inplayScoreData: PropTypes.array,
  filterChanged: PropTypes.bool,
  setFilterChanged: PropTypes.func,
  trigger2DataBySet: PropTypes.object,
};

export default Trigger2;
