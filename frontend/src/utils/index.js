export const getWinner = (scores) => {
  const cleandScores = scores.replaceAll(' ', '');
  const scoresArr = cleandScores.split(',');
  if (scoresArr.length === 0) {
    return 0;
  }
  let winner = 0,
    loser = 0;
  for (let i = 0; i < scoresArr.length; i++) {
    const subScores = scoresArr[i].split('-');
    if (parseInt(subScores[0]) > parseInt(subScores[1])) {
      winner++;
    }

    if (parseInt(subScores[0]) < parseInt(subScores[1])) {
      loser++;
    }
  }

  return winner > loser ? 1 : 2;
};

export const formatDate = (date) => {
  let d = date;
  if (typeof d === 'string') {
    d = new Date(d);
  }

  let month = '' + (d.getMonth() + 1);
  let day = '' + d.getDate();
  let year = d.getFullYear();

  if (month.length < 2) month = '0' + month;
  if (day.length < 2) day = '0' + day;

  return [year, month, day].join('-');
};

export const formatDateTime = (timestamp) => {
  let d = new Date(timestamp * 1000);
  let month = '' + (d.getMonth() + 1);
  let day = '' + d.getDate();
  let year = d.getFullYear();

  if (month.length < 2) month = '0' + month;
  if (day.length < 2) day = '0' + day;
  // Will display date in 16.03.2021 format
  const date = [day, month, year].join('.');

  const hours = d.getHours();
  const minutes = '0' + d.getMinutes();

  // Will display time in 10:30 format
  const time = hours + ':' + minutes.substr(-2);

  return [date, time];
};

export const sortByTime = (data, player1_id, player2_id) => {
  data[player1_id].sort(function (a, b) {
    return b['time'] - a['time'];
  }); // Sort biggest first
  data[player2_id].sort(function (a, b) {
    return b['time'] - a['time'];
  }); // Sort biggest first
  data['opponents'].sort(function (a, b) {
    return b['time'] - a['time'];
  }); // Sort biggest first
};

export const filterData = (player1_id, player2_id, relationData, filters) => {
  let filteredData = { ...relationData };
  const filterSet1 = filters['set1'];
  const filterSet2 = filters['set2'];

  // filtering by surface
  if (filters['surface'] != 'ALL') {
    filteredData[player1_id] = filteredData[player1_id].filter(
      (item) => item['surface'] === filters['surface']
    );
    filteredData[player2_id] = filteredData[player2_id].filter(
      (item) => item['surface'] === filters['surface']
    );
    filteredData['opponents'] = filteredData['opponents'].filter(
      (item) => item['surface'] === filters['surface']
    );
  }

  // filtering by sets
  if (filterSet1 !== 'ALL') {
    filteredData[player1_id] = filteredData[player1_id].filter(
      (item) =>
        item['scores'] !== '' &&
        item['scores'].split(',').length >= parseInt(filterSet1)
    );
  }
  if (filterSet2 !== 'ALL') {
    filteredData[player2_id] = filteredData[player2_id].filter(
      (item) =>
        item['scores'] !== '' &&
        item['scores'].split(',').length >= parseInt(filterSet2)
    );
  }

  let player1_ranking =
    filteredData[player1_id].length > 0
      ? filteredData[player1_id][0]['p_ranking']
      : 99999;
  player1_ranking = player1_ranking === null ? 99999 : player1_ranking;
  let player2_ranking =
    filteredData[player2_id].length > 0
      ? filteredData[player2_id][0]['p_ranking']
      : 99999;
  player2_ranking = player2_ranking === null ? 99999 : player2_ranking;

  // filtering by SRO or SO
  if (filters['opponent'] === 'SRO') {
    if (player2_ranking !== 99999) {
      filteredData[player1_id] = filteredData[player1_id].filter(
        (item) =>
          item['o_ranking'] != null &&
          item['o_ranking'] <= player2_ranking + 30 &&
          item['o_ranking'] >= player2_ranking - 30
      );
    } else {
      filteredData[player1_id] = filteredData[player1_id].filter(
        (item) => item['o_ranking'] == null
      );
    }

    if (player1_ranking !== 99999) {
      filteredData[player2_id] = filteredData[player2_id].filter(
        (item) =>
          item['o_ranking'] != null &&
          item['o_ranking'] <= player1_ranking + 30 &&
          item['o_ranking'] >= player1_ranking - 30
      );
    } else {
      filteredData[player2_id] = filteredData[player2_id].filter(
        (item) => item['o_ranking'] == null
      );
    }
  } else if (filters['opponent'] === 'SO') {
    const oRankings1 = filteredData[player1_id].map((item) => {
      if (item['o_ranking'] != null) {
        return item['o_ranking'];
      }
    });
    const ids = filteredData[player2_id].map((item) => {
      if (oRankings1.includes(item['o_ranking'])) {
        return item['o_id'];
      }
    });

    filteredData[player1_id] = filteredData[player1_id].filter((item) =>
      ids.includes(item['o_id'])
    );
    filteredData[player1_id].sort(function (a, b) {
      return a['o_ranking'] - b['o_ranking'];
    }); // Sort youngest first
    filteredData[player2_id] = filteredData[player2_id].filter((item) =>
      ids.includes(item['o_id'])
    );
    filteredData[player2_id].sort(function (a, b) {
      return a['o_ranking'] - b['o_ranking'];
    }); // Sort youngest first
  }

  // filtering by HIR, LOR
  let filterRankingPlayer1 = [];
  let filterRankingPlayer2 = [];
  if (filters['rankDiff1'] === 'HIR') {
    filterRankingPlayer1 = filteredData[player1_id].filter(
      (item) => item['o_ranking'] != null && item['o_ranking'] < player1_ranking
    );
  } else if (filters['rankDiff1'] === 'LOR') {
    filterRankingPlayer1 = filteredData[player1_id].filter(
      (item) =>
        item['o_ranking'] === null || item['o_ranking'] > player1_ranking
    );
  } else {
    filterRankingPlayer1 = filteredData[player1_id];
  }

  if (filters['rankDiff2'] === 'HIR') {
    filterRankingPlayer2 = filteredData[player2_id].filter(
      (item) => item['o_ranking'] != null && item['o_ranking'] < player2_ranking
    );
  } else if (filters['rankDiff2'] === 'LOR') {
    filterRankingPlayer2 = filteredData[player2_id].filter(
      (item) =>
        item['o_ranking'] === null || item['o_ranking'] > player2_ranking
    );
  } else {
    filterRankingPlayer2 = filteredData[player2_id];
  }

  // calculate BRW, BRL, GAH, and GRA
  const filterLimit = filters['limit'];

  // -- player1
  filteredData[player1_id] = [];
  filteredData[player2_id] = [];
  let totalPlayerBRW = 0;
  let totalPlayerBRL = 0;
  let totalPlayerGAH = 0;
  let totalPlayerGIR = [];
  let ww = 0;
  let wl = 0;
  let lw = 0;
  let ll = 0;
  let raw = [];
  let ral = [];
  let limitCnt = 0;
  filteredData['performance'] = {};

  for (let i = 0; i < filterRankingPlayer1.length; i++) {
    const item = filterRankingPlayer1[i];
    const pBRW = JSON.parse(item['p_brw']);
    const pBRL = JSON.parse(item['p_brl']);
    const pGAH = JSON.parse(item['p_gah']);
    const pDepth = JSON.parse(item['p_depths']);
    let sumBRW = 0;
    let sumBRL = 0;
    let sumGAH = 0;
    if (filterSet1 === 'ALL') {
      for (let j = 0; j < 5; j++) {
        sumBRW += pBRW[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
        sumBRL += pBRL[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
        sumGAH += pGAH[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
        if (pDepth[j] !== 0) {
          totalPlayerGIR.push(pDepth[j]);
        }
      }
    } else {
      sumBRW += pBRW[parseInt(filterSet1) - 1].reduce(
        (a, b) => parseInt(a) + parseInt(b),
        0
      );
      sumBRL += pBRL[parseInt(filterSet1) - 1].reduce(
        (a, b) => parseInt(a) + parseInt(b),
        0
      );
      sumGAH += pGAH[parseInt(filterSet1) - 1].reduce(
        (a, b) => parseInt(a) + parseInt(b),
        0
      );
      let depth = pDepth[parseInt(filterSet1) - 1];
      if (depth !== 0) {
        totalPlayerGIR.push(depth);
      }
    }
    totalPlayerBRW += sumBRW;
    totalPlayerBRL += sumBRL;
    totalPlayerGAH += sumGAH;
    const pWW = JSON.parse(item['p_ww']);
    const pWL = JSON.parse(item['p_wl']);
    const pLW = JSON.parse(item['p_lw']);
    const pLL = JSON.parse(item['p_ll']);
    const oRanking = item['o_ranking'] === null ? 501 : item['o_ranking'];

    const totalWW = pWW.reduce((a, b) => parseInt(a) + parseInt(b), 0);
    const totalLW = pLW.reduce((a, b) => parseInt(a) + parseInt(b), 0);
    const totalWL = pWL.reduce((a, b) => parseInt(a) + parseInt(b), 0);
    const totalLL = pLL.reduce((a, b) => parseInt(a) + parseInt(b), 0);
    if (totalWW + totalLW >= totalWL + totalLL) {
      raw.push(oRanking);
    } else {
      ral.push(oRanking);
    }

    if (filterSet1 === 'ALL') {
      ww += totalWW;
      wl += totalLW;
      lw += totalWL;
      ll += totalLL;
    } else {
      ww += parseInt(pWW[parseInt(filterSet1) - 1]);
      wl += parseInt(pWL[parseInt(filterSet1) - 1]);
      lw += parseInt(pLW[parseInt(filterSet1) - 1]);
      ll += parseInt(pLL[parseInt(filterSet1) - 1]);
    }

    filteredData[player1_id].push(item);
    limitCnt++;
    if (limitCnt === filterLimit) {
      break;
    }
  }

  let sumGRA = totalPlayerGIR.reduce((a, b) => parseInt(a) + parseInt(b), 0);
  let pGRA =
    totalPlayerGIR.length > 0
      ? (sumGRA / totalPlayerGIR.length).toFixed(2)
      : '0';
  filteredData['performance'][player1_id] = {
    pBRW: totalPlayerBRW,
    pBRL: totalPlayerBRL,
    pGAH: totalPlayerGAH,
    pGRA: pGRA,
    pWW: ww,
    pWL: wl,
    pLW: lw,
    pLL: ll,
    RAW: raw,
    RAL: ral,
  };

  // -- player2
  totalPlayerBRW = 0;
  totalPlayerBRL = 0;
  totalPlayerGAH = 0;
  totalPlayerGIR = [];
  ww = 0;
  wl = 0;
  lw = 0;
  ll = 0;
  raw = [];
  ral = [];
  limitCnt = 0;
  for (let i = 0; i < filterRankingPlayer2.length; i++) {
    const item = filterRankingPlayer2[i];
    const pBRW = JSON.parse(item['p_brw']);
    const pBRL = JSON.parse(item['p_brl']);
    const pGAH = JSON.parse(item['p_gah']);
    const pDepth = JSON.parse(item['p_depths']);
    let sumBRW = 0;
    let sumBRL = 0;
    let sumGAH = 0;
    if (filterSet2 === 'ALL') {
      for (let j = 0; j < 5; j++) {
        sumBRW += pBRW[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
        sumBRL += pBRL[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
        sumGAH += pGAH[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
        if (pDepth[j] !== 0) {
          totalPlayerGIR.push(pDepth[j]);
        }
      }
    } else {
      sumBRW += pBRW[parseInt(filterSet2) - 1].reduce(
        (a, b) => parseInt(a) + parseInt(b),
        0
      );
      sumBRL += pBRL[parseInt(filterSet2) - 1].reduce(
        (a, b) => parseInt(a) + parseInt(b),
        0
      );
      sumGAH += pGAH[parseInt(filterSet2) - 1].reduce(
        (a, b) => parseInt(a) + parseInt(b),
        0
      );
      const depth = pDepth[parseInt(filterSet2) - 1];
      if (depth !== 0) {
        totalPlayerGIR.push(depth);
      }
    }
    totalPlayerBRW += sumBRW;
    totalPlayerBRL += sumBRL;
    totalPlayerGAH += sumGAH;
    const pWW = JSON.parse(item['p_ww']);
    const pWL = JSON.parse(item['p_wl']);
    const pLW = JSON.parse(item['p_lw']);
    const pLL = JSON.parse(item['p_ll']);
    const oRanking = item['o_ranking'] === null ? 501 : item['o_ranking'];

    const totalWW = pWW.reduce((a, b) => parseInt(a) + parseInt(b), 0);
    const totalLW = pLW.reduce((a, b) => parseInt(a) + parseInt(b), 0);
    const totalWL = pWL.reduce((a, b) => parseInt(a) + parseInt(b), 0);
    const totalLL = pLL.reduce((a, b) => parseInt(a) + parseInt(b), 0);
    if (totalWW + totalLW >= totalWL + totalLL) {
      raw.push(oRanking);
    } else {
      ral.push(oRanking);
    }

    if (filterSet2 === 'ALL') {
      ww += totalWW;
      wl += totalLW;
      lw += totalWL;
      ll += totalLL;
    } else {
      ww += parseInt(pWW[parseInt(filterSet2) - 1]);
      wl += parseInt(pWL[parseInt(filterSet2) - 1]);
      lw += parseInt(pLW[parseInt(filterSet2) - 1]);
      ll += parseInt(pLL[parseInt(filterSet2) - 1]);
    }

    filteredData[player2_id].push(item);
    limitCnt++;
    if (limitCnt === filterLimit) {
      break;
    }
  }

  sumGRA = totalPlayerGIR.reduce((a, b) => parseInt(a) + parseInt(b), 0);
  pGRA =
    totalPlayerGIR.length > 0
      ? (sumGRA / totalPlayerGIR.length).toFixed(2)
      : '0';
  filteredData['performance'][player2_id] = {
    pBRW: totalPlayerBRW,
    pBRL: totalPlayerBRL,
    pGAH: totalPlayerGAH,
    pGRA: pGRA,
    pWW: ww,
    pWL: wl,
    pLW: lw,
    pLL: ll,
    RAW: raw,
    RAL: ral,
  };

  // get RW, RL, GIR for opponents
  let filteredPlayer1 = [];
  filteredData[player1_id].map((p) => {
    let opponents = filteredData['opponents'].filter(
      (o) => o['o_id'] === p['o_id']
    );
    // filter by set
    if (filterSet1 !== 'ALL') {
      opponents = opponents.filter((o) => o['sets'] >= parseInt(filterSet1));
    }

    // filter by HIR, LOR
    if (filters['rankDiff1'] === 'HIR') {
      opponents = opponents.filter(
        (o) => o['oo_ranking'] != 501 && o['oo_ranking'] < o['o_ranking']
      );
    } else if (filters['rankDiff1'] === 'LOR') {
      opponents = opponents.filter(
        (o) => o['oo_ranking'] === 501 || o['oo_ranking'] > o['o_ranking']
      );
    }

    let limitOpponentCnt = 0;
    let totalOpponentGIR = [];
    let oRW = [];
    let oRL = [];
    for (let i = 0; i < opponents.length; i++) {
      const oDepth = JSON.parse(opponents[i]['depths']);
      if (filterSet1 === 'ALL') {
        for (let j = 0; j < 5; j++) {
          if (oDepth[j] !== 0) {
            totalOpponentGIR.push(oDepth[j]);
          }
        }
      } else {
        const depth = oDepth[parseInt(filterSet1) - 1];
        if (depth !== 0) {
          totalOpponentGIR.push(depth);
        }
      }

      if (opponents[i]['won'] === 1) {
        oRW.push(opponents[i]['oo_ranking']);
      } else {
        oRL.push(opponents[i]['oo_ranking']);
      }
      limitOpponentCnt++;
      if (limitOpponentCnt === filterLimit) {
        break;
      }
    }
    const sumOGIR = totalOpponentGIR.reduce(
      (a, b) => parseInt(a) + parseInt(b),
      0
    );
    const oGIR =
      totalOpponentGIR.length > 0
        ? (sumOGIR / totalOpponentGIR.length).toFixed(2)
        : '0';
    p['oGIR'] = oGIR;
    if (oRW.length === 0) {
      p['oRW'] = 0;
    } else {
      const sumORW = oRW.reduce((a, b) => parseInt(a) + parseInt(b), 0);
      p['oRW'] = sumORW / oRW.length;
    }
    if (oRL.length === 0) {
      p['oRL'] = 0;
    } else {
      const sumORL = oRL.reduce((a, b) => parseInt(a) + parseInt(b), 0);
      p['oRL'] = sumORL / oRL.length;
    }
    filteredPlayer1.push(p);
  });

  // opponent 2
  let filteredPlayer2 = [];
  filteredData[player2_id].map((p) => {
    let opponents = filteredData['opponents'].filter(
      (o) => o['o_id'] === p['o_id']
    );
    // filter by set
    if (filterSet2 !== 'ALL') {
      opponents = opponents.filter((o) => o['sets'] >= parseInt(filterSet2));
    }

    // filter by HIR, LOR
    if (filters['rankDiff2'] === 'HIR') {
      opponents = opponents.filter(
        (o) => o['oo_ranking'] != 501 && o['oo_ranking'] < o['o_ranking']
      );
    } else if (filters['rankDiff2'] === 'LOR') {
      opponents = opponents.filter(
        (o) => o['oo_ranking'] === 501 || o['oo_ranking'] > o['o_ranking']
      );
    }

    let limitOpponentCnt = 0;
    let totalOpponentGIR = [];
    let oRW = [];
    let oRL = [];
    for (let i = 0; i < opponents.length; i++) {
      const oDepth = JSON.parse(opponents[i]['depths']);
      if (filterSet2 === 'ALL') {
        for (let j = 0; j < 5; j++) {
          if (oDepth[j] !== 0) {
            totalOpponentGIR.push(oDepth[j]);
          }
        }
      } else {
        const depth = oDepth[parseInt(filterSet2) - 1];
        if (depth !== 0) {
          totalOpponentGIR.push(depth);
        }
      }

      if (opponents[i]['won'] === 1) {
        oRW.push(opponents[i]['oo_ranking']);
      } else {
        oRL.push(opponents[i]['oo_ranking']);
      }
      limitOpponentCnt++;
      if (limitOpponentCnt === filterLimit) {
        break;
      }
    }
    const sumOGIR = totalOpponentGIR.reduce(
      (a, b) => parseInt(a) + parseInt(b),
      0
    );
    const oGIR =
      totalOpponentGIR.length > 0
        ? (sumOGIR / totalOpponentGIR.length).toFixed(2)
        : '0';
    p['oGIR'] = oGIR;
    if (oRW.length === 0) {
      p['oRW'] = 0;
    } else {
      const sumORW = oRW.reduce((a, b) => parseInt(a) + parseInt(b), 0);
      p['oRW'] = sumORW / oRW.length;
    }
    if (oRL.length === 0) {
      p['oRL'] = 0;
    } else {
      const sumORL = oRL.reduce((a, b) => parseInt(a) + parseInt(b), 0);
      p['oRL'] = sumORL / oRL.length;
    }
    filteredPlayer2.push(p);
  });
  let playersData = {};
  playersData[player1_id] = filteredPlayer1;
  playersData[player2_id] = filteredPlayer2;
  playersData['performance'] = filteredData['performance'];
  return playersData;
};

/**
 * Filter by rank (button group)
 * @param {array} data
 */
export const filterByRankOdd = (data, type, range, match_type = 0) => {
  if (data !== undefined) {
    const filteredData = [];
    let matchData = [...data];
    matchData.map((item) => {
      let enabledRank = 0;
      if (type === '1') {
        // All
        enabledRank = 1;
      } else if (type === '2') {
        // One Ranked
        if (
          item['player1_ranking'] !== '-' ||
          item['player2_ranking'] !== '-'
        ) {
          enabledRank = 1;
        }
      } else if (type === '3') {
        // Both Unranked
        if (
          item['player1_ranking'] === '-' &&
          item['player2_ranking'] === '-'
        ) {
          enabledRank = 1;
        }
      }
      if (enabledRank) {
        // get the current score
        const range_1 = range[0] / 10;
        const range_2 = range[1] / 10;
        const player1_odd =
          item['player1_odd'] !== null
            ? parseFloat(item['player1_odd']).toFixed(2)
            : null;
        const player2_odd =
          item['player2_odd'] !== null
            ? parseFloat(item['player2_odd']).toFixed(2)
            : null;
        if (range_1 === 0.9) {
          if (
            ((player1_odd === null || player1_odd > 0) &&
              player1_odd <= range_2) ||
            ((player2_odd === null || player2_odd > 0) &&
              player2_odd <= range_2)
          ) {
            if (match_type === 1) {
              item['ss'] = '';
              item['points'] = '';
              item['indicator'] = '';
              filteredData.push(item);
            } else {
              filteredData.push(item);
            }
          }
        } else {
          if (
            (player1_odd >= range_1 && player1_odd <= range_2) ||
            (player2_odd >= range_1 && player2_odd <= range_2)
          ) {
            if (match_type === 1) {
              item['ss'] = '';
              item['points'] = '';
              item['indicator'] = '';
              filteredData.push(item);
            } else {
              filteredData.push(item);
            }
          }
        }
      }
    });
    return filteredData;
  }
  return [];
};

export const addInplayScores = (inplayData, scores) => {
  let filteredData = [];
  inplayData.map((item) => {
    scores.map((score) => {
      if (item['event_id'] === score['event_id']) {
        let data = { ...item };
        data['ss'] = score['ss'];
        data['points'] = score['points'];
        data['indicator'] = score['indicator'];
        filteredData.push(data);
      }
    });
  });
  return filteredData;
};

export const getCurrentInplayScores = (event_id, data) => {
  const currentEvent = data.filter((item) => item['event_id'] === event_id);
  return currentEvent[0];
};

// trigger1
export const filterTrigger1 = (
  triggerData,
  trigger1DataBySet,
  gameDiff = 1
) => {
  let filteredData1 = [...trigger1DataBySet['set1']]; // set 1
  let filteredData2 = [...trigger1DataBySet['set2']]; // set 2
  let filteredData3 = [...trigger1DataBySet['set3']]; // set 3

  // remove old items
  filteredData1 = filteredData1.filter((f) => f['ss'].split(',').length === 1);
  filteredData2 = filteredData2.filter((f) => f['ss'].split(',').length === 2);
  filteredData3 = filteredData3.filter((f) => f['ss'].split(',').length === 3);

  triggerData['inplay_detail'].map((item) => {
    // check who is w
    const setScores = item['ss'].split(',');
    let score = [];
    const setLength = setScores.length;
    if (setLength >= 1) {
      score = setScores[setLength - 1].split('-');
    }
    if (score.length === 2) {
      const home = parseInt(score[0]);
      const away = parseInt(score[1]);
      let winner = -1;
      if (home - away === gameDiff) {
        winner = 1;
      } else if (away - home === gameDiff) {
        winner = 2;
      }

      if (winner !== -1) {
        // get GIR of player1
        let player1GIR = [];
        triggerData['players_detail'][item['player1_id']].map((p) => {
          const pDepth = JSON.parse(p['p_depths']);
          player1GIR.push(pDepth[setLength - 1]);
        });
        let sumPlayer1GRA = player1GIR.reduce(
          (a, b) => parseInt(a) + parseInt(b),
          0
        );
        let p1GRA =
          player1GIR.length > 0 ? sumPlayer1GRA / player1GIR.length : 0;

        // get GIR of player2
        let player2GIR = [];
        triggerData['players_detail'][item['player2_id']].map((p) => {
          const pDepth = JSON.parse(p['p_depths']);
          player2GIR.push(pDepth[setLength - 1]);
        });
        let sumPlayer2GRA = player2GIR.reduce(
          (a, b) => parseInt(a) + parseInt(b),
          0
        );
        let p2GRA =
          player2GIR.length > 0 ? sumPlayer2GRA / player2GIR.length : 0;

        if (
          (winner === 1 && p2GRA > p1GRA) ||
          (winner === 2 && p1GRA > p2GRA)
        ) {
          if (setLength === 1) {
            let itemExist = false;
            filteredData1.map((f) => {
              if (f['event_id'] === item['event_id']) {
                itemExist = true;
              }
            });
            // add new match
            if (!itemExist) {
              filteredData1.push(item);
            }
          } else if (setLength === 2) {
            let itemExist = false;
            filteredData2.map((f) => {
              if (f['event_id'] === item['event_id']) {
                itemExist = true;
              }
            });
            // add new match
            if (!itemExist) {
              filteredData2.push(item);
            }
          } else if (setLength === 3) {
            let itemExist = false;
            filteredData3.map((f) => {
              if (f['event_id'] === item['event_id']) {
                itemExist = true;
              }
            });
            // add new match
            if (!itemExist) {
              filteredData3.push(item);
            }
          }
        }
      }
    }
  });
  return {
    set1: filteredData1,
    set2: filteredData2,
    set3: filteredData3,
  };
};

// check value in one array is in other array or not
export const itemNotExist = (array1, array2) => {
  for (let i = 0; i < array1.length; i++) {
    if (!array2.includes(array1[i]['event_id'])) {
      return true;
    }
  }
  return false;
};
