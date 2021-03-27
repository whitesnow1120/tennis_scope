import React, { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Helmet } from 'react-helmet';

import CustomDatePicker from '../components/CustomDatePicker';
import MatchItem from '../components/MatchItem';

import { getHistoryData } from '../apis';
import { GET_HISTORY_DATA } from '../store/actions/types';

import { SITE_SEO_TITLE, SITE_SEO_DESCRIPTION } from '../common/Constants';

const History = () => {
  const dispatch = useDispatch();
  const { historyData, historyDate } = useSelector((state) => state.tennis);

  useEffect(() => {
    const loadHistoryData = async () => {
      const response = await getHistoryData(historyDate);
      if (response.status === 200) {
        dispatch({
          type: GET_HISTORY_DATA,
          payload: response.data,
        });
      } else {
        dispatch({ type: GET_HISTORY_DATA, payload: [] });
      }
      // Call the async function again
      setTimeout(function () {
        loadHistoryData();
      }, 1000 * 60 * 10);
    };

    loadHistoryData();
  }, [historyDate]);

  return (
    <>
      <Helmet>
        <title>{SITE_SEO_TITLE} : History</title>
        <meta property="og:title" content={SITE_SEO_TITLE} />
        <meta name="description" content={SITE_SEO_DESCRIPTION} />
        <meta property="og:description" content={SITE_SEO_DESCRIPTION} />
      </Helmet>
      <section className="section history">
        <div className="container-fluid">
          <div className="datepicker-container">
            <CustomDatePicker />
          </div>
          <div className="row mt-4">
            {historyData.length > 0 ? (
              historyData.map((item) => (
                <MatchItem key={item.id} item={item} type="history" />
              ))
            ) : (
              <span className="no-result">There is no history data</span>
            )}
          </div>
        </div>
      </section>
    </>
  );
};

export default History;
