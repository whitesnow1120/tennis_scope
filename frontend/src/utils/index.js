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
};

export const filterData = (player1_id, player2_id, relationData, filters) => {
  let filteredData = { ...relationData };
  // filtering by surface
  if (filters['surface'] != 'ALL') {
    filteredData[player1_id] = filteredData[player1_id].filter(
      (item) => item['surface'] === filters['surface']
    );
    filteredData[player2_id] = filteredData[player2_id].filter(
      (item) => item['surface'] === filters['surface']
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

  // calculate BRW, BRL and GAH
  const filterLimit = filters['limit'];
  const filterSet1 = filters['set1'];
  const filterSet2 = filters['set2'];

  // -- player1
  filteredData[player1_id] = [];
  filteredData[player2_id] = [];
  let totalPlayerBRW = 0;
  let totalPlayerBRL = 0;
  let totalPlayerGAH = 0;
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
    let sumBRW = 0;
    let sumBRL = 0;
    let sumGAH = 0;
    if (filterSet1 === 'ALL') {
      for (let j = 0; j < 5; j++) {
        sumBRW += pBRW[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
        sumBRL += pBRL[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
        sumGAH += pGAH[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
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

  filteredData['performance'][player1_id] = {
    pBRW: totalPlayerBRW,
    pBRL: totalPlayerBRL,
    pGAH: totalPlayerGAH,
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
    let sumBRW = 0;
    let sumBRL = 0;
    let sumGAH = 0;
    if (filterSet2 === 'ALL') {
      for (let j = 0; j < 5; j++) {
        sumBRW += pBRW[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
        sumBRL += pBRL[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
        sumGAH += pGAH[j].reduce((a, b) => parseInt(a) + parseInt(b), 0);
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

  filteredData['performance'][player2_id] = {
    pBRW: totalPlayerBRW,
    pBRL: totalPlayerBRL,
    pGAH: totalPlayerGAH,
    pWW: ww,
    pWL: wl,
    pLW: lw,
    pLL: ll,
    RAW: raw,
    RAL: ral,
  };
  return filteredData;
};

/**
 * Filter by rank (button group)
 * @param {array} data
 */
export const filterByRankOdd = (data, type, range) => {
  const filteredData = [];
  data.map((item) => {
    let enabledRank = 0;
    if (type === 1) {
      // All
      enabledRank = 1;
    } else if (type === 2) {
      // Both Ranked
      if (item['player1_ranking'] !== '-' && item['player2_ranking'] !== '-') {
        enabledRank = 1;
      }
    } else if (type === 3) {
      // One Ranked
      if (item['player1_ranking'] !== '-' || item['player2_ranking'] !== '-') {
        enabledRank = 1;
      }
    } else if (type === 4) {
      // Both Unranked
      if (item['player1_ranking'] === '-' && item['player2_ranking'] === '-') {
        enabledRank = 1;
      }
    }
    if (enabledRank) {
      if (
        (item['player1_odd'] >= range[0] && item['player1_odd'] <= range[1]) ||
        (item['player2_odd'] >= range[0] && item['player2_odd'] <= range[1])
      ) {
        filteredData.push(item);
      }
    }
  });
  return filteredData;
};
