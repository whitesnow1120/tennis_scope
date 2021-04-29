import React, { useEffect, useState } from 'react';
import { Helmet } from 'react-helmet';

import { getRobotsData } from '../apis';
import { calculateRobotPercent } from '../utils';
import { SITE_SEO_TITLE, SITE_SEO_DESCRIPTION } from '../common/Constants';
import MatchRobotItem from '../components/MatchRobotItem';

const Robots = () => {
  const [robotData, setRobotData] = useState([]);

  useEffect(() => {
    const loadRobotsData = async () => {
      const response = await getRobotsData();
      if (response.status === 200) {
        const data = calculateRobotPercent(response.data);
        setRobotData(data);
      } else {
        setRobotData([]);
      }
    };

    loadRobotsData();
  }, []);

  return (
    <>
      <Helmet>
        <title>{SITE_SEO_TITLE} : Robots</title>
        <meta property="og:title" content={SITE_SEO_TITLE} />
        <meta name="description" content={SITE_SEO_DESCRIPTION} />
        <meta property="og:description" content={SITE_SEO_DESCRIPTION} />
      </Helmet>
      <section className="section robots">
        <div className="container-fluid">
          <div className="row mt-4">
            <div className="col-12 trigger1">
              <div className="robot-rule">
                <span>BRW + GAH + ODD BASED (Ranked)</span>
              </div>
              <div className="trigger-border"></div>
            </div>
            {robotData.length > 0 ? (
              robotData[0].map((item, index) => (
                <MatchRobotItem key={index} item={item} robotType={43} />
              ))
            ) : (
              <></>
            )}
          </div>
          <div className="row mt-4">
            <div className="col-12 trigger1">
              <div className="robot-rule">
                <span>BRW + GAH + ODD + Balance BASED (Ranked)</span>
              </div>
              <div className="trigger-border"></div>
            </div>
            {robotData.length > 0 ? (
              robotData[1].map((item, index) => (
                <MatchRobotItem key={index} item={item} />
              ))
            ) : (
              <></>
            )}
          </div>
          <div className="row mt-4">
            <div className="col-12 trigger1">
              <div className="robot-rule">
                <span>Balance BASED (Ranked)</span>
              </div>
              <div className="trigger-border"></div>
            </div>
            {robotData.length > 0 ? (
              robotData[2].map((item, index) => (
                <MatchRobotItem key={index} item={item} />
              ))
            ) : (
              <></>
            )}
          </div>
          <div className="row mt-4">
            <div className="col-12 trigger1">
              <div className="robot-rule">
                <span>Balance BASED (UnRanked)</span>
              </div>
              <div className="trigger-border"></div>
            </div>
            {robotData.length > 0 ? (
              robotData[3].map((item, index) => (
                <MatchRobotItem key={index} item={item} />
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

export default Robots;
