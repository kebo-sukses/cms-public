param(
    [switch]$NoCache
)

$image = 'calius-tests:latest'
if ($NoCache) {
    docker build --no-cache -t $image .
} else {
    docker build -t $image .
}

docker run --rm -v ${PWD}:/app -w /app $image
