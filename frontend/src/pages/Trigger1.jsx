import React, { useState, useEffect } from 'react';
import useSound from 'use-sound';
import { Helmet } from 'react-helmet';
import { css } from '@emotion/core';
import BounceLoader from 'react-spinners/BounceLoader';

import { getTrigger1Data } from '../apis';
import ding from '../assets/ding.mp3';
import MatchItem from '../components/MatchItem';
import { SITE_SEO_TITLE, SITE_SEO_DESCRIPTION } from '../common/Constants';

const Trigger1 = () => {
  const [play] = useSound(ding, { interrupt: true });
  const [trigger1Data, setTrigger1Data] = useState([]);
  const [loading, setLoading] = useState(false);
  const [newEventIds, setNewEventIds] = useState([]);

  const override = css`
    display: block;
    margin: 0 auto;
    border-color: red;
  `;

  useEffect(() => {
    const loadTrigger1Data = async () => {
      const response = await getTrigger1Data();
      if (response.status === 200) {
        // check new trigger data
        const data = response.data;
        // old trigger data event ids
        let oldEventIds = JSON.parse(
          localStorage.getItem('oldTrigger1EventIds')
        );

        let newTrigger1Events = [];
        if (oldEventIds != null) {
          newTrigger1Events = data.filter(
            (item) => !oldEventIds.includes(item['event_id'])
          );
        } else {
          newTrigger1Events = data;
        }

        let allEventIds = [];
        let newIds = [];
        if (newTrigger1Events.length > 0) {
          play();
          localStorage.setItem('trigger1Clicked', '1');
          newTrigger1Events.map((item) => {
            newIds.push(item['event_id']);
          });
          allEventIds = oldEventIds.concat(newIds);
        } else {
          localStorage.setItem('trigger1Clicked', '2');
          allEventIds = oldEventIds;
        }
        // concat old and new event ids
        localStorage.setItem(
          'oldTrigger1EventIds',
          JSON.stringify(allEventIds)
        );
        setNewEventIds(newIds);
        setTrigger1Data(response.data);
      } else {
        setTrigger1Data([]);
      }
      // Call the async function again
      setTimeout(function () {
        loadTrigger1Data();
      }, 1000 * 60 * 10);
    };

    loadTrigger1Data();
  }, []);

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
          <div className="row mt-4">
            {trigger1Data.length > 0 ? (
              trigger1Data.map((item) => (
                <MatchItem
                  key={item.id}
                  item={item}
                  type="trigger1"
                  newIds={newEventIds}
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

export default Trigger1;
