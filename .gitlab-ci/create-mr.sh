#!/usr/bin/env bash

MERGE_REQUESTS_API="${CI_API_V4_URL}/projects/${CI_PROJECT_ID}/merge_requests"

# Check whether we already have a MR created..
LISTMR=`curl --silent "${MERGE_REQUESTS_API}?state=opened" --header "PRIVATE-TOKEN:${CI_GIT_TOKEN}"`;
COUNTBRANCHES=`echo ${LISTMR} | grep -o "\"source_branch\":\"${SOURCE}\"" | wc -l`;

# No MR found, let's create a new one..
if [ ${COUNTBRANCHES} -eq "0" ]; then
  curl -X POST "${MERGE_REQUESTS_API}" \
      --header "PRIVATE-TOKEN:${CI_GIT_TOKEN}" \
      --header "Content-Type: application/json" \
      --data "{
        \"id\": ${CI_PROJECT_ID},
        \"source_branch\": \"${SOURCE}\",
        \"target_branch\": \"${TARGET}\",
        \"title\": \"${TITLE}\",
        \"assignee_id\": \"${GITLAB_USER_ID}\",
        \"description\":\"${DESCRIPTION}\n\n---\nTriggered by ${CI_MERGE_REQUEST_PROJECT_URL}/merge_requests/${CI_MERGE_REQUEST_IID}\",
        \"labels\": \"${LABELS}\",
        \"remove_source_branch\": true,
        \"allow_collaboration\": false
      }";

  exit 1;
fi

echo "Skipping: A merge request already exists.";
exit 1;
